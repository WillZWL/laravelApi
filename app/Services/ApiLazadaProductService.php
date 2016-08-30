<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use lazada api package
use App\Repository\LazadaMws\LazadaProductList;
use App\Repository\LazadaMws\LazadaProductUpdate;

class ApiLazadaProductService extends ApiBaseService implements ApiPlatformProductInterface
{
    const PENDING_PRICE = 2;
    const COMPLETE_PRICE = 8;
    const PENDING_INVENTORY = 4;
    const COMPLETE_INVENTORY = 16;

    private $storeCurrency;
    public function __construct()
    {
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

    public function submitProductPrice($storeName)
    {
        return $this->runProductUpdate($storeName,'pendingPrice');
    }

    public function submitProductInventory($storeName)
    {
        return $this->runProductUpdate($storeName,'pendingInventory');
    }

    protected function runProductUpdate($storeName,$action)
    {   
        $processStatus = array(
            'pendingPrice' => self::PENDING_PRICE,
            'pendingInventory' => self::PENDING_INVENTORY,
        );
        $pendingSkuGroups = MarketplaceSkuMapping::where('process_status', '&', $processStatus[$action])
            ->where('listing_status', '=', 'Y')
            ->where('marketplace_id', 'like', '%LAZADA')
            ->where('asin', '=', 'test')
            ->get()
            ->groupBy('mp_control_id');
        foreach ($pendingSkuGroups as $mpControlId => $pendingSkuGroup) {
            $marketplaceControl = MpControl::find($mpControlId);
            $storeName = $marketplaceControl->marketplace_id.$marketplaceControl->country_id;
            $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
            $xmlData .= '<Request>';
            foreach ($pendingSkuGroup as $index => $pendingSku) {
                $messageDom = '<Product>';
                $messageDom .= '<SellerSku>'.$pendingSku->marketplace_sku.'</SellerSku>';
                if ($processStatus[$action] == self::PENDING_PRICE) {
                    $messageDom .= '<Price>'.round($pendingSku->price * 1.3, 2).'</Price>';
                    $messageDom .= '<SalePrice>'.$pendingSku->price.'</SalePrice>';
                }
                if ($processStatus[$action] == self::PENDING_INVENTORY) {
                    $messageDom .= '<Quantity>'.$pendingSku->inventory.'</Quantity>';
                }
                $messageDom .= '</Product>';
                $xmlData .= $messageDom;
            }
            $xmlData .= '</Request>';
            print_r($xmlData);
            $this->lazadaProductUpdate = new LazadaProductUpdate($storeName);
            $this->storeCurrency = $this->lazadaProductUpdate->getStoreCurrency();
            $this->saveDataToFile(serialize($xmlData), 'pendingProductPriceOrInventory');
            $result = $this->lazadaProductUpdate->submitXmlData($xmlData);
            print_r($result);
            $this->saveDataToFile(serialize($result), 'submitProductPriceOrInventory');
            if($result){
                if ($processStatus[$action] == self::PENDING_PRICE) {
                    $pendingSkuGroup->transform(function ($pendingSku) {
                        $pendingSku->process_status ^= self::PENDING_PRICE;
                        $pendingSku->process_status |= self::COMPLETE_PRICE;
                    });
                }
                if ($processStatus[$action] == self::PENDING_INVENTORY) {
                    $pendingSkuGroup->transform(function ($pendingSku) {
                        $pendingSku->process_status ^= self::PENDING_INVENTORY;
                        $pendingSku->process_status |= self::COMPLETE_INVENTORY;
                        $pendingSku->save(); 
                    });
                }
                return $result;
            }
        }
    }

    public function submitProductCreate($storeName)
    {
        $pendingSkuGroups = MarketplaceSkuMapping::PendingProductSkuGroups($query, '%LAZADA');
        foreach ($pendingSkuGroups as $mpControlId => $pendingSkuGroup) {
            $marketplaceControl = MpControl::find($mpControlId);
            $storeName = $marketplaceControl->marketplace_id.$marketplaceControl->country_id;
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
    }

    public function submitProductUpdate()
    {
        $pendingSkuGroups = MarketplaceSkuMapping::PendingProductSkuGroups($query, '%LAZADA');
        foreach ($pendingSkuGroups as $mpControlId => $pendingSkuGroup) {
            $marketplaceControl = MpControl::find($mpControlId);
            $storeName = $marketplaceControl->marketplace_id.$marketplaceControl->country_id;
            $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
            $xmlData .= '<Request>';
            foreach ($pendingSkuGroup as $index => $pendingSku) {
                $messageDom = '<Product>';
                $messageDom .= '<SellerSku>'.$pendingSku->marketplace_sku.'</SellerSku>';
                $messageDom .= '<Description><![CDATA['.$pendingSku->detail_desc.']]</Description>';
                $messageDom .= '<Brand><![CDATA['.$pendingSku->brand_name.']]</Brand>';
                $messageDom .= '<Price>'.$pendingSku->price.'</Price>';
                $messageDom .= '<Condition>'.$pendingSku->condition.'</Condition>';
                $messageDom .= '<Quantity>'.$pendingSku->marketplace_sku.'</Quantity>';
                $messageDom .= '</Product>';
                $xmlData .= $messageDom;
            }
            $xmlData .= '</Request>';
            $this->lazadaProductUpdate = new LazadaProductUpdate($storeName);
            $this->storeCurrency = $this->lazadaProductUpdate->getStoreCurrency();
            $result = $this->lazadaProductUpdate->submitXmlData($xmlData);
            $this->saveDataToFile(serialize($result), 'updateProduct');
            //return $result;
        }
    }
}
