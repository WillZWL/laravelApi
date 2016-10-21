<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;

//use lazada api package
use App\Repository\LazadaMws\LazadaProductList;
use App\Repository\LazadaMws\LazadaProductUpdate;
use App\Repository\LazadaMws\LazadaFeedStatus;
use App\Repository\LazadaMws\LazadaSearchSPUs;
use App\Repository\LazadaMws\LazadaCategoryAttributes;
use App\Repository\LazadaMws\LazadaCategoryTree;
use App\Repository\LazadaMws\LazadaProductBrand;
use Config;

class ApiLazadaProductService implements ApiPlatformProductInterface
{
    use ApiBaseProductTraitService;
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
        $this->saveDataToFile(serialize($orginProductList), 'getProductList');

        return $orginProductList;
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
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
            $responseXml = $this->lazadaProductUpdate->submitXmlData($xmlData);
            $this->saveDataToFile(serialize($responseXml), 'submitProductPriceOrInventory');
            if($responseXml){
                $responseData = new \SimpleXMLElement($responseXml);
                $requestId = (string) $responseData->Head->RequestId;
                if($requestId){
                    $this->createOrUpdatePlatformMarketProductFeed($storeName,$requestId);
                    $this->updatePendingProductProcessStatus($processStatusProduct,$processStatus); 
                }
            }
        }
    } 

    public function getProductUpdateFeedBack($storeName,$productUpdateFeeds)
    {
        $message = null;
        $this->lazadaFeedStatus = new LazadaFeedStatus($storeName);
        foreach ($productUpdateFeeds as $productUpdateFeed) {
            $this->lazadaFeedStatus->setFeedId($productUpdateFeed->feed_submission_id);
            $result = $this->lazadaFeedStatus->fetchFeedStatus();
            if($result){
                $this->saveDataToFile(serialize($result), 'getProductUpdateFeedBack');
                foreach ($result as $resultDetail){
                    if ($resultDetail["FailedRecords"] > 0 ){
                        if($resultDetail["FailedRecords"] == 1){
                             $message = $resultDetail["FeedErrors"]["Error"]["Message"];
                        }else{
                            foreach ($resultDetail["FeedErrors"]["Error"] as  $errorDetail) {
                                $message .= $errorDetail["Message"]."\r\n";
                            }
                        }
                        $productUpdateFeed->feed_processing_status = '_COMPLETE_WITH_ERROR_';
                    }else{
                        $productUpdateFeed->feed_processing_status = '_COMPLETE_';
                    }
                }
                $productUpdateFeed->save();
            }
        }
        if($message){
            $alertEmail = $this->stores[$storeName]["userId"];
            $subject = $storeName." price and inventory update failed!";
            $this->sendMailMessage($alertEmail, $subject, $message);
        }
    }

    public function submitProductCreate($storeName,$pendingSkuGroup)
    {   
        foreach ($pendingSkuGroup as $key => $product) {
            $productTemplate = $this->getProductTemplate($storeName,$product);
            $this->lazadaProductCreate = new LazadaProductCreate($storeName);
            $responseData = $this->lazadaProductCreate->createProduct($product);
            $this->saveDataToFile(serialize($responseData), 'CreateProduct');
        }
    }

    public function getProductTemplate($storeName,$product)
    {
        $productTemplate = null;
        $this->lazadaSearchSPUs = new LazadaSearchSPUs($storeName);
        $this->lazadaSearchSPUs->setSearch("Apple");
        $spus = $this->lazadaSearchSPUs->searchSPUs();
        if($spus){
            foreach ($spus as $key => $spu) {
                if($key == 2){
                    $productTemplate = $spu;
                    $productTemplate["categoryAttributes"] = $this->getCategoryAttributes($storeName,$spu["PrimaryCategory"]);
                }
            }
        }else{
            $this->lazadaCategoryTree = new LazadaCategoryTree($storeName);
            $categoryTrees = $this->lazadaCategoryTree->fetchCategoryTree();
            foreach ($categoryTrees as $key => $categoryTree) {
                if($key == 2)
                $productTemplate["categoryAttributes"] = $this->getCategoryAttributes($storeName,$categoryTree["categoryId"]);
            }
            $this->lazadaProductBrand = new LazadaProductBrand($storeName);
            $brandList = $this->lazadaProductBrand->fetchBrandList();
            $productTemplate["brand"] = $brandList[0];
        }
        return $productTemplate;
    }

    public function getCategoryAttributes($storeName,$categoryId)
    {
        $this->lazadaCategoryAttributes = new LazadaCategoryAttributes($storeName);
        $this->lazadaCategoryAttributes->setPrimaryCategory($categoryId);
        return $this->lazadaCategoryAttributes->fetchCategoryAttributes();
    }
    public function getQcStatus($storeName,$requestId)
    {
        $this->lazadaQcStatus = new LazadaQcStatus($storeName);
        $this->lazadaQcStatus->setSkuSellerList($requestId);
        $responseData = $this->lazadaQcStatus->fetchQcStatus();
        $this->saveDataToFile(serialize($responseData), 'getQcStatus');
        return $responseData;
    }

    public function submitProductUpdate($storeName)
    {
        $pendingSkuGroup = MarketplaceSkuMapping::PendingProductSkuGroup($storeName);
        
    }
}
