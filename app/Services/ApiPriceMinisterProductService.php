<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use lazada api package
use App\Repository\PriceMinisterMws\PriceMinisterProductList;
use App\Repository\PriceMinisterMws\PriceMinisterProductUpdate;

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
        $this->priceMinisterProductList = new PriceMinisterProductList($storeName);
        $this->storeCurrency = $this->priceMinisterProductList->getStoreCurrency();
        //$dateTime=date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
        $dateTime = date(\DateTime::ISO8601, strtotime('2016-07-31'));
        $this->priceMinisterProductList->setCreatedBefore($dateTime);
        $orginProductList = $this->priceMinisterProductList->fetchProductList();
        print_r($orginProductList);
        exit();
        $this->saveDataToFile(serialize($orginProductList), 'getProductList');

        return $orginProductList;
    }

    public function submitProductPrice()
    {
        return $this->runProductUpdate('pendingPrice');
    }

    public function submitProductInventory()
    {
        return $this->runProductUpdate('pendingInventory');
    }

    protected function runProductUpdate($action)
    {
        $processStatus = array(
            'pendingPrice' => self::PENDING_PRICE,
            'pendingInventory' => self::PENDING_INVENTORY,
        );
        $pendingSkuGroups = MarketplaceSkuMapping::where('process_status', '&', $processStatus[$action])
            ->where('listing_status', '=', 'Y')
            ->where('marketplace_id', 'like', '%PRICEMINISTER')
            ->get()
            ->groupBy('mp_control_id');
            
        foreach ($pendingSkuGroups as $mpControlId => $pendingSkuGroup) {
            $marketplaceControl = MpControl::find($mpControlId);
            $storeName = $marketplaceControl->marketplace_id.$marketplaceControl->country_id;
            $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
            $xmlData .= '<items>';
            foreach ($pendingSkuGroup as $index => $pendingSku) {
                $messageDom = '<item>';
                $messageDom .= '<attributes>'.$pendingSku->marketplace_sku.'</attributes>';
                if ($processStatus[$action] == self::PENDING_PRICE) {
                    $messageDom .= '<key>sellingPrice</key>';
                    $messageDom .= '<value>'.$pendingSku->price.'</value>';
                }
                if ($processStatus[$action] == self::PENDING_INVENTORY) {
                    $messageDom .= '<key>qty</key>';
                    $messageDom .= '<value>'.$pendingSku->inventory.'</value>';
                }
                $messageDom .= '</item>';
                $xmlData .= $messageDom;
            }
            $xmlData .= '</items>';
           /* $filename ='update-prodcut-'.date("Y-m-d-00-00-00") ;
            $xmlFile = \Storage::disk('skuMapping')->put($filename.".xml",$xmlData);
        
            exit();*/
            $this->priceMinisterProductUpdate = new PriceMinisterProductUpdate($storeName);
            $this->storeCurrency = $this->priceMinisterProductUpdate->getStoreCurrency();
            $result = $this->priceMinisterProductUpdate->submitXmlData($xmlData);
            $this->saveDataToFile(serialize($result), 'submitProductPriceOrInventory');

            return $result;
        }
    }

    public function submitProductCreate()
    {
        /*$pendingSkuGroups = MarketplaceSkuMapping::PendingProductSkuGroups($query, '%LAZADA');
        foreach ($pendingSkuGroups as $mpControlId => $pendingSkuGroup) {
            $marketplaceControl = MpControl::find($mpControlId);
            $storeName = $marketplaceControl->marketplace_id.$marketplaceControl->country_id;
            $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
            $xmlData .= '<Request>';
            foreach ($pendingSkuGroup as $index => $pendingSku) {
                $messageDom = '<Product>';
                $messageDom .= '<Status>'.$pendingSku->marketplace_sku.'</Status>';
                $messageDom .= '</Product>';
                $xmlData .= $messageDom;
            }
            $xmlData .= '</Request>';
            $this->lazadaProductCreate = new LazadaProductCreate($storeName);
            $this->storeCurrency = $this->lazadaProductCreate->getStoreCurrency();
            $result = $this->lazadaProductCreate->submitXmlData($xmlData);
            $this->saveDataToFile(serialize($result), 'updateProduct');
            //return $result;*/
        }
    }

    public function submitProductUpdate()
    {
        /*$pendingSkuGroups = MarketplaceSkuMapping::PendingProductSkuGroups($query, '%LAZADA');
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
            //return $result;*/
        }
    }
}
