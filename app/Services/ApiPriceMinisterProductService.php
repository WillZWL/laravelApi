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
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus[$action]);
            
        if(!$processStatusProduct->isEmpty()){
            $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
            $xmlData .= '<items>';
            foreach ($processStatusProduct as $index => $pendingSku) {
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

    public function submitProductCreate($storeName)
    {
        $pendingSkuGroup = MarketplaceSkuMapping::PendingProductSkuGroup($storeNames);
        if(!$pendingSkuGroup->isEmpty()){
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
        $pendingSkuGroup = MarketplaceSkuMapping::PendingProductSkuGroup($storeName);
        if(!$pendingSkuGroup->isEmpty()){
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
            $this->priceMinisterProductUpdate = new PriceMinisterProductUpdate($storeName);
            $this->storeCurrency = $this->priceMinisterProductUpdate->getStoreCurrency();
            $result = $this->priceMinisterProductUpdate->submitXmlData($xmlData);
            $this->saveDataToFile(serialize($result), 'updateProduct');
            //return $result;*/
        }
    }
}
