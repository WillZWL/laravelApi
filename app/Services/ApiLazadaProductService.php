<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use lazada api package
use App\Repository\LazadaMws\LazadaProductList;
use App\Repository\LazadaMws\LazadaProductUpdate;
use App\Repository\LazadaMws\LazadaFeedStatus;
use Config;

class ApiLazadaProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    private $storeCurrency;
    public function __construct()
    {
        $this->stores =  Config::get('lazada-mws.store');
    }

    public function getPlatformId()
    {
        return 'Lazada';
    }

    public function getProductList($storeName)
    {
        $this->lazadaProductList = new LazadaProductList($storeName);
        $this->storeCurrency = $this->lazadaProductList->getStoreCurrency();
        //$dateTime=date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
        $dateTime = date(\DateTime::ISO8601, strtotime('2016-07-31'));
        $this->lazadaProductList->setCreatedBefore($dateTime);
        $orginProductList = $this->lazadaProductList->fetchProductList();
        print_r($orginProductList);
        exit();
        $this->saveDataToFile(serialize($orginProductList), 'getProductList');

        return $orginProductList;
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = self::PENDING_PRICE | self::PENDING_INVENTORY;
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);
        if(!$processStatusProduct->isEmpty()){
            $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
            $xmlData .= '<Request>';
            foreach ($processStatusProduct as $index => $pendingSku) {
                $messageDom = '<Product>';
                $messageDom .= '<SellerSku>'.$pendingSku->marketplace_sku.'</SellerSku>';
                $messageDom .= '<Price>'.round($pendingSku->price * 1.3, 2).'</Price>';
                $messageDom .= '<SalePrice>'.$pendingSku->price.'</SalePrice>';
                $messageDom .= '<SaleStartDate>'.date('Y-m-d').'</SaleStartDate>';
                $messageDom .= '<SaleEndDate>'.date('Y-m-d', strtotime('+4 year')).'</SaleEndDate>';
                $messageDom .= '<Quantity>'.$pendingSku->inventory.'</Quantity>';
                $messageDom .= '</Product>';
                $xmlData .= $messageDom;
            }
            $xmlData .= '</Request>';
            $this->lazadaProductUpdate = new LazadaProductUpdate($storeName);
            $this->storeCurrency = $this->lazadaProductUpdate->getStoreCurrency();
            $this->saveDataToFile(serialize($xmlData), 'pendingProductPriceOrInventory');
            $requestId = $this->lazadaProductUpdate->submitXmlData($xmlData);
            $this->saveDataToFile(serialize($requestId), 'submitProductPriceOrInventory');
            if($requestId){
                $this->lazadaFeedStatus = new LazadaFeedStatus($storeName);
                $this->lazadaFeedStatus->setFeedId($requestId);
                $result = $this->lazadaFeedStatus->fetchFeedStatus();
                foreach ($result as $resultDetail){
                    if ($resultDetail["FailedRecords"] > 0 ){
                        $message = null;
                        $alertEmail = $this->stores[$storeName]["userId"];
                        $subject = $storeName." price and inventory update failed!";
                        if($resultDetail["FailedRecords"] == 1){
                             $message = $resultDetail["FeedErrors"]["Error"]["Message"];
                        }else{
                            foreach ($resultDetail["FeedErrors"]["Error"] as  $errorDetail) {
                                $message .= $errorDetail["Message"]."\r\n";
                            }
                        }
                        $this->sendMailMessage($alertEmail, $subject, $message );
                    }
                }
                $this->updatePendingProductProcessStatus($processStatusProduct,$processStatus);
            }
        }
    }

    public function submitProductCreate($storeName)
    {   
        $pendingSkuGroup = MarketplaceSkuMapping::PendingProductSkuGroup($storeName);
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<Request>';
        foreach ($pendingSkuGroup as $index => $pendingSku) {
            $messageDom = '<Product>';
            $messageDom .= '<Status>'.$pendingSku->marketplace_sku.'</Status>';
            $messageDom .= '<Name><![CDATA['.$pendingSku->prod_name.']]</Name>';
            $messageDom .= '<Variation>'.$pendingSku->marketplace_sku.'</Variation>';
            $messageDom .= '<PrimaryCategory>'.$pendingSku->marketplace_sku.'</PrimaryCategory>';
            $messageDom .= '<Categories>'.$pendingSku->marketplace_sku.'</Categories>';
            $messageDom .= '<Description><![CDATA['.$pendingSku->detail_desc.']]</Description>';
            $messageDom .= '<Brand><![CDATA['.$pendingSku->brand_name.']]</Brand>';
            $messageDom .= '<Price>'.$pendingSku->marketplace_sku.'</Price>';
            $messageDom .= '<SalePrice>'.$pendingSku->marketplace_sku.'</SalePrice>';
            $messageDom .= '<SaleStartDate>'.$pendingSku->marketplace_sku.'</SaleStartDate>';
            $messageDom .= '<SaleEndDate>'.$pendingSku->marketplace_sku.'</SaleEndDate>';
            $messageDom .= '<TaxClass>'.$pendingSku->marketplace_sku.'</TaxClass>';
            $messageDom .= '<ShipmentType>'.$pendingSku->marketplace_sku.'</ShipmentType>';
            $messageDom .= '<ProductId>'.$pendingSku->marketplace_sku.'</ProductId>';
            $messageDom .= '<Condition>'.$pendingSku->condition.'</Condition>';
            $messageDom .= '<ProductData>';
            $messageDom .= '<Megapixels>'.$pendingSku->marketplace_sku.'</Megapixels>';
            $messageDom .= '<OpticalZoom>'.$pendingSku->marketplace_sku.'</OpticalZoom>';
            $messageDom .= '<SystemMemory>'.$pendingSku->marketplace_sku.'</SystemMemory>';
            $messageDom .= '<NumberCpus>'.$pendingSku->marketplace_sku.'</NumberCpus>';
            $messageDom .= '<Network>'.$pendingSku->marketplace_sku.'</Network>';
            $messageDom .= '</ProductData>';
            $messageDom .= '<Quantity>'.$pendingSku->marketplace_sku.'</Quantity>';
            $messageDom .= '</Product>';
            $xmlData .= $messageDom;
        }
        $xmlData .= '</Request>';
        $this->lazadaProductCreate = new LazadaProductCreate($storeName);
        $this->storeCurrency = $this->lazadaProductCreate->getStoreCurrency();
        $result = $this->lazadaProductCreate->submitXmlData($xmlData);
        $this->saveDataToFile(serialize($result), 'updateProduct');
        //return $result;
        
    }

    public function submitProductUpdate($storeName)
    {
        $pendingSkuGroup = MarketplaceSkuMapping::PendingProductSkuGroup($storeName);
        foreach ($pendingSkuGroup as $index => $pendingSku) {
            
        }
    }
}
