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
                    $this->createOrUpdatePlatformMarketProductFeed($storeName,$responseFileId);
                    $this->updatePendingProductProcessStatus($processStatusProduct,$processStatus);
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
            $errorStatus = false;
            $this->priceMinisterImportReport->setFileId($productUpdateFeed->feed_submission_id);
            $reports = $this->priceMinisterImportReport->getImportReport();
            if($reports){
                $this->saveDataToFile(serialize($reports), 'getProductUpdateFeedBack');
                foreach ($reports as $report) {
                    if($report["errors"]){
                        $errorStatus = true;
                        $message .= "Seller Sku ".$report["sku"]." error message: \r\n";
                        foreach ($report["errors"] as $error) {
                            $message .= $error["error_text"]."\r\n";
                        }
                    }
                }
                if($errorStatus){
                    $productUpdateFeed->feed_processing_status = '_COMPLETE_WITH_ERROR_';
                }else{
                    $productUpdateFeed->feed_processing_status = '_COMPLETE_';
                }
                $productUpdateFeed->save();
            }
        }
        if($message){
            $alertEmail = $this->stores[$storeName]["email"];
            $subject = $storeName." price or inventory update failed!";
            $this->sendMailMessage($alertEmail, $subject, $message);
        }
    }

    public function submitProductCreate($storeName)
    {
        
    }

    public function submitProductUpdate()
    {
        
    }
}
