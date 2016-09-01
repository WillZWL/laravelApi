<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use lazada api package
use App\Repository\PriceMinisterMws\PriceMinisterProductList;
use App\Repository\PriceMinisterMws\PriceMinisterProductUpdate;

class ApiPriceMinisterProductService extends ApiBaseService implements ApiPlatformProductInterface
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
            $xmlData = '<?xml version="1.0" encoding="UTF-8"?>';
            $xmlData .= '<items>';
            foreach ($processStatusProduct as $index => $pendingSku) {
                $messageDom = '<item>';
                $messageDom .=  '<attributes>';
                $messageDom .=   '<advert>';
                $messageDom .=   '<attribute>';
                $messageDom .=    '<key>sellerReference</key>';
                $messageDom .=    '<value>'.$pendingSku->marketplace_sku.'</value>';
                $messageDom .=   '</attribute>';
                $messageDom .=   '<attribute>';
                if ($processStatus[$action] == self::PENDING_PRICE) {
                    $messageDom .= '<key>sellingPrice</key>';
                    $messageDom .= '<value>'.$pendingSku->price.'</value>';
                }
                if ($processStatus[$action] == self::PENDING_INVENTORY) {
                    $messageDom .= '<key>qty</key>';
                    $messageDom .= '<value>'.$pendingSku->inventory.'</value>';
                }
                $messageDom .=   '</attribute>';
                $messageDom .=   '</advert>';
                $messageDom .=  '</attributes>';
                $messageDom .= '</item>';
                $xmlData .= $messageDom;
            }
            $xmlData .= '</items>';
            $filename ='update-prodcut-'.date("Y-m-d-H-i-s") ;
            $result = \Storage::disk('xml')->put($filename.".xml",$xmlData);
            $xmlFile=\Storage::disk('xml')->getDriver()->getAdapter()->getPathPrefix().$filename.".xml";
            $this->priceMinisterProductUpdate = new PriceMinisterProductUpdate($storeName);
            $this->storeCurrency = $this->priceMinisterProductUpdate->getStoreCurrency();
            $result = $this->priceMinisterProductUpdate->submitXmlFile($xmlFile);
            $this->saveDataToFile(serialize($result), 'submitProductPriceOrInventory');
            if($result){
                $this->updatePendingProductProcessStatus($processStatusProduct,$processStatus[$action]);
                return $result;
            }
        }
    }

    public function submitProductCreate($storeName)
    {
        
    }

    public function submitProductUpdate()
    {
        
    }
}
