<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use Config;

//use lazada api package
use App\Repository\PriceMinisterMws\PriceMinisterProductList;
use App\Repository\PriceMinisterMws\PriceMinisterProductUpdate;
use App\Repository\PriceMinisterMws\PriceMinisterImportReport;
use App\Repository\PriceMinisterMws\PriceMinisterProductModel;
use App\Repository\PriceMinisterMws\PriceMinisterProductCreate;

class ApiPriceMinisterProductService implements ApiPlatformProductInterface
{
    use ApiBaseProductTraitService;
    private $storeCurrency;

    public function __construct()
    {
        $this->stores =  Config::get('priceminister-mws.store');
    }

    public function getPlatformId()
    {
        return 'PriceMinister';
    }

    public function getProductList($storeName)
    {
        $this->priceMinisterProductList = new PriceMinisterProductList($storeName);
        $this->storeCurrency = $this->priceMinisterProductList->getStoreCurrency();
        return $orginProductList;
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);
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
                $messageDom .=      '<key>sellingPrice</key>';
                $messageDom .=      '<value>'.$pendingSku->price.'</value>';
                $messageDom .=   '</attribute>';
                $messageDom .=   '<attribute>';
                $messageDom .=      '<key>qty</key>';
                $messageDom .=      '<value>'.$pendingSku->inventory.'</value>';
                $messageDom .=   '</attribute>';
                $messageDom .=   '</advert>';
                $messageDom .=  '</attributes>';
                $messageDom .= '</item>';
                $xmlData .= $messageDom;
            }
            $xmlData .= '</items>';
            $filename =$this->getPlatformId().'/update-prodcut-'.date("Y-m-d-H-i-s") ;
            $result = \Storage::disk('xml')->put($filename.".xml",$xmlData);
            $xmlFile=\Storage::disk('xml')->getDriver()->getAdapter()->getPathPrefix().$filename.".xml";
            $this->priceMinisterProductUpdate = new PriceMinisterProductUpdate($storeName);
            $this->storeCurrency = $this->priceMinisterProductUpdate->getStoreCurrency();
            $responseXml = $this->priceMinisterProductUpdate->submitXmlFile($xmlFile);
            $this->saveDataToFile(serialize($responseXml), 'submitProductPriceOrInventory');
            if($responseXml){
                $responseData = new \SimpleXMLElement($responseXml);
                if($responseData->response->status == "OK"){
                    $responseFileId = (string) $responseData->response->importid;
                    $platformMarketProductFeed = $this->createOrUpdatePlatformMarketProductFeed($storeName,$responseFileId);
                    if($platformMarketProductFeed->id){
                        foreach ($processStatusProduct as $pendingSku) {
                           $this->createOrUpdatePlatformMarketFeedBatch("update_inventory_and_price",$platformMarketProductFeed->id,$pendingSku->id,$pendingSku->marketplace_sku,$processStatus);
                        }
                    }
                    return $responseFileId;
                }
            }
        }
    }

    public function getProductUpdateFeedBack($storeName,$productUpdateFeeds)
    {
        $this->priceMinisterImportReport = new PriceMinisterImportReport($storeName);
        $message = null;
        foreach ($productUpdateFeeds as $productUpdateFeed) {
            $errorSku = array();
            $this->priceMinisterImportReport->setFileId($productUpdateFeed->feed_submission_id);
            $reports = $this->priceMinisterImportReport->getImportReport();
            if($reports){
                $this->saveDataToFile(serialize($reports), 'getProductUpdateFeedBack');
                foreach ($reports as $report) {
                    if(empty($report["errors"])){
                        $errorSku[] = $report["sku"];
                        $message .= "Seller Sku ".$report["sku"]." error message: \r\n";
                        foreach ($report["errors"] as $error) {
                           if(isset($error["error_text"])){
                                $message .= $error["error_text"]."\r\n";
                           }
                        }
                    }
                }
                $this->confirmPlatformMarketInventoryStatus($productUpdateFeed,$errorSku);
            }
        }
        if($message){
            $alertEmail = $this->stores[$storeName]["email"];
            $subject = $storeName." price or inventory update failed!";
            $this->sendMailMessage($alertEmail, $subject, $message);
        }
    }

    public function submitProductCreate($storeName,$pendingSkuGroup)
    {
        $this->priceMinisterProductCreate = new PriceMinisterProductCreate($storeName);
        $productGroup = $this->mappingProductAttributes($storeName,$pendingSkuGroup);
        $xmlData = $this->priceMinisterProductCreate->getRequestXmlData($productGroup);
        $responseXml = $this->priceMinisterProductCreate->submitXmlFile($xmlData);
        $this->saveDataToFile(serialize($responseXml), 'submitProductCreate');
    }

    public function getProductTypes($storeName)
    {
        $this->priceMinisterProductModel = new PriceMinisterProductModel($storeName);
        $productTypes = $this->priceMinisterProductModel->getProductTypes();
        return $productTypes;
    }

    public function getProductTemplate($storeName,$productTypes)
    {
        $this->priceMinisterProductModel = new PriceMinisterProductModel($storeName);
        $this->priceMinisterProductModel->setProductType($productTypes);
        $productTemplate = $this->priceMinisterProductModel->fetchProductModel();
        return $productTemplate;
    }

    public function submitProductUpdate()
    {
        
    }
}
