<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use App\Models\PlatformMarketInventory;
use App\Models\Store;

//use lazada api package
use App\Repository\LazadaMws\LazadaProductList;
use App\Repository\LazadaMws\LazadaProductUpdate;
use App\Repository\LazadaMws\LazadaFeedStatus;
use App\Repository\LazadaMws\LazadaSearchSPUs;
use App\Repository\LazadaMws\LazadaCategoryAttributes;
use App\Repository\LazadaMws\LazadaCategoryTree;
use App\Repository\LazadaMws\LazadaProductBrand;
use App\Repository\LazadaMws\LazadaProductCreate;
use App\Repository\LazadaMws\LazadaUpdatePriceQuantity;
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
        $this->lazadaProductList->setFilter("all");
        $this->lazadaProductList->setLimit("500");
        $orginProductList = $this->lazadaProductList->fetchProductList();
        //$this->saveDataToFile(serialize($orginProductList), 'getProductList');
        return $orginProductList;
    }

    public function exportPlatformMarketInventoryAvailable($storeName)
    {
        $store = $this->getPlatformStore($storeName);
        $platformMarketInventory = PlatformMarketInventory::where("store_id",$store->id)
                    ->select("store_id","warehouse_id","mattel_sku","dc_sku","marketplace_sku","inventory")
                    ->get()
                    ->toArray();
        if(!empty($platformMarketInventory)){
            $orginProductList = $this->getProductList($storeName);
            if(isset($this->stores[$storeName]["new_api"])){
                foreach ($orginProductList as $orginProduct) {
                    if(isset($orginProduct["Skus"]["Sku"]["SellerSku"])){
                        $inventory[$orginProduct["Skus"]["Sku"]["SellerSku"]] = $orginProduct["Skus"]["Sku"]["Available"];
                    }else{
                        foreach ($orginProduct["Skus"]["Sku"] as $sku) {
                            $inventory[$sku["SellerSku"]] = $sku["Available"];
                        }
                    }
                }
            }else{
                foreach ($orginProductList as $value) {
                    $inventory[$value["SellerSku"]] = $value["Available"];
                }
            }
            $newPlatformMarketInventory[] = array("store_id","warehouse_id","mattel_sku","dc_sku","marketplace_sku","inventory","Available");
            foreach ($platformMarketInventory as $value) {
                if($value["inventory"] != $inventory[$value["marketplace_sku"]]){
                    $value["Available"] = $inventory[$value["marketplace_sku"]];
                    $newPlatformMarketInventory[] = $value;
                }
            }
            $cellDataArr[$storeName] = $newPlatformMarketInventory;
            $path = \Storage::disk('report')->getDriver()->getAdapter()->getPathPrefix()."LazadaProduct";
            $this->generateMultipleSheetsExcel($storeName,$cellDataArr,$path);
        }
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        $processStatusProduct = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);
        if(!$processStatusProduct->isEmpty()){
            if(isset($this->stores[$storeName]["new_api"])){
                $xmlDataArr = $this->getNewApiUpdatePriceQuantityXmlData($processStatusProduct,20);
                foreach ($xmlDataArr as $xmlData) {
                    $result = $this->fetchNewApiLazadaUpdatePriceQuantity($storeName,$xmlData);
                    $errorSku = isset($result["errorSku"]) ? $result["errorSku"] : null;
                    $this->updatePlatformMarketProductStatus($processStatusProduct,$errorSku,$processStatus);
                }
            }else{
                $xmlData = $this->getUpdatePriceQuantityXmlData($processStatusProduct);
                $feedId = $this->fetchLazadaUpdatePriceQuantity($storeName,$xmlData);
                if($feedId){
                    foreach ($processStatusProduct as $pendingSku) {
                        $this->createOrUpdatePlatformMarketFeedBatch("update_inventory_and_price",$feedId,$pendingSku->id,$pendingSku->marketplace_sku,$processStatus);
                    }
                }
            }
        }
    }

    public function getProductUpdateFeedBack($storeName,$productUpdateFeeds)
    {
        $message = null;
        $this->lazadaFeedStatus = new LazadaFeedStatus($storeName);
        foreach ($productUpdateFeeds as $productUpdateFeed) {
            $errorSku = array();
            $this->lazadaFeedStatus->setFeedId($productUpdateFeed->feed_submission_id);
            $result = $this->lazadaFeedStatus->fetchFeedStatus();
            if($result){
                $this->saveDataToFile(serialize($result), 'getProductUpdateFeedBack');
                foreach ($result as $resultDetail){
                    if ($resultDetail["FailedRecords"] > 0 ){
                        if($resultDetail["FailedRecords"] == 1){
                             $message = $resultDetail["FeedErrors"]["Error"]["Message"];
                             $errorSku[] = $resultDetail["FeedErrors"]["Error"]["SellerSku"];
                        }else{
                            foreach ($resultDetail["FeedErrors"]["Error"] as  $errorDetail) {
                                $message .= $errorDetail["Message"]."\r\n";
                                $errorSku[] = $errorDetail["SellerSku"];
                            }
                        }
                    }
                }
                $this->confirmPlatformMarketInventoryStatus($productUpdateFeed,$errorSku);
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
            $attributeOptions = $this->getPlatformMarketAttributeOptions($storeName);
            $this->lazadaProductCreate = new LazadaProductCreate($storeName);
            $responseData = $this->lazadaProductCreate->createProduct($product,$attributeOptions);
            $this->saveDataToFile(serialize($responseData), 'CreateProduct');
        }
    }

    public function searchLazadaSPUs($storeName,$param)
    {
        $productTemplate = null;
        $this->lazadaSearchSPUs = new LazadaSearchSPUs($storeName);
        if($param["search"])
        $this->lazadaSearchSPUs->setSearch($param["search"]);
        if($param["categoryId"])
        $this->lazadaSearchSPUs->setCategoryId($param["categoryId"]);
        return $this->lazadaSearchSPUs->searchSPUs();
    }

    public function getLazadaCategoryTree($storeName)
    {
        $this->lazadaCategoryTree = new LazadaCategoryTree($storeName);
        $categoryTrees = $this->lazadaCategoryTree->fetchCategoryTree();
        return $categoryTrees;
    }

    public function getCategoryAttributes($storeName,$categoryId)
    {
        $this->lazadaCategoryAttributes = new LazadaCategoryAttributes($storeName);
        $this->lazadaCategoryAttributes->setPrimaryCategory($categoryId);
        return $this->lazadaCategoryAttributes->fetchCategoryAttributes();
    }

    public function getLazadaBrands($storeName)
    {
        $this->lazadaProductBrand = new LazadaProductBrand($storeName);
        return $this->lazadaProductBrand->fetchBrandList();
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

    public function updatePlatformMarketMattleInventory()
    {
        $processStatus = PlatformMarketConstService::PENDING_INVENTORY;
        //the lazada maximum of SellerSku
        $inventorys = PlatformMarketInventory::where("update_status","1")->with("merchantProductMapping")->get();
        if(!$inventorys->isEmpty()){
            $inventoryGroups = $inventorys->groupBy("store_id");
            foreach ($inventoryGroups as $storeId => $inventoryGroup) {
                $store = Store::find($storeId);
                $storeName = $store->store_code.$store->marketplace.$store->country;
                if(!$inventoryGroup->isEmpty()){
                    if(isset($this->stores[$storeName]["new_api"])){
                        $xmlDataArr = $this->getNewApiMattleUpdateQuantityXmlData($inventoryGroup,20);
                        foreach ($xmlDataArr as $xmlData) {
                            $result = $this->fetchNewApiLazadaUpdatePriceQuantity($storeName,$xmlData);
                            $errorSku = isset($result["errorSku"]) ? $result["errorSku"] : null;
                            $this->updatePlatformMarketProductStatus($inventoryGroup,$errorSku);
                        }
                    }else{
                        $xmlData = $this->getMattleUpdateQuantityXmlData($inventoryGroup);
                        $feedId = $this->fetchLazadaUpdatePriceQuantity($storeName,$xmlData);
                        if($feedId){
                            foreach ($inventoryGroup as $platformMarketInventory) {
                               $this->createOrUpdatePlatformMarketFeedBatch("mattle_update_inventory",$feedId,$platformMarketInventory->id,$platformMarketInventory->marketplace_sku,$processStatus);
                            }
                        }
                    }
                }
            }
        }
    }

    public function fetchNewApiLazadaUpdatePriceQuantity($storeName,$xmlData)
    {
        $message = null; $errorDetail = array();
        $this->lazadaUpdatePriceQuantity = new LazadaUpdatePriceQuantity($storeName);
        $responseXml = $this->lazadaUpdatePriceQuantity->submitXmlData($xmlData);
        if($responseXml){
            $responseData = new \SimpleXMLElement($responseXml);
            $errorDetail = $responseData->Body->Errors;
            if(!empty($errorDetail)){
                foreach ($errorDetail->ErrorDetail as $key => $error) {
                    $message .= "Marketplace Sku ".$error->SellerSku." error message ".$error->Message."\r\n";
                    $result["errorSku"][] = $error->SellerSku;
                }
                if($message){
                    $alertEmail = $this->stores[$storeName]["userId"];
                    $subject = $storeName." price and inventory update failed!";
                    $this->sendMailMessage($alertEmail, $subject, $message);
                }
           }else{
                $result["feedId"] = $responseData->Head->RequestId;
           }
           return $result;
        }
    }

    public function fetchLazadaUpdatePriceQuantity($storeName,$xmlData)
    {
        $this->lazadaProductUpdate = new LazadaProductUpdate($storeName);
        $responseXml = $this->lazadaProductUpdate->submitXmlData($xmlData);
        if($responseXml){
            $responseData = new \SimpleXMLElement($responseXml);
            $requestId = (string) $responseData->Head->RequestId;
            if($requestId){
                $platformMarketProductFeed = $this->createOrUpdatePlatformMarketProductFeed($storeName,$requestId);
                return $platformMarketProductFeed->id;
            }
        }
    }

    private function getNewApiUpdatePriceQuantityXmlData($processStatusProduct,$limitMaxNumber)
    {
        $messageXmlArr = array();
        $newInventoryGroup = array_chunk($processStatusProduct->toArray(),$limitMaxNumber,true);
         foreach ($newInventoryGroup as $limitInventoryGroup) {
            $messageXml = null;
           foreach ($limitInventoryGroup as $pendingSku) {
                $messageDom = '<Sku>';
                $messageDom .= '<SellerSku>'.$pendingSku['marketplace_sku'].'</SellerSku>';
                $messageDom .= '<Quantity>'.$pendingSku['inventory'].'</Quantity>';
                $messageDom .= '<Price>'.round($pendingSku['price'] * 1.3, 2).'</Price>';
                $messageDom .= '<SalePrice>'.$pendingSku['price'].'</SalePrice>';
                $messageDom .= '<SaleStartDate>'.date('Y-m-d').'</SaleStartDate>';
                $messageDom .= '<SaleEndDate>'.date('Y-m-d', strtotime('+4 year')).'</SaleEndDate>';
                $messageDom .= '</Sku>';
                $messageXml .= $messageDom;
           }
           $messageXmlArr[] = $messageXml;
        }
        if(!empty($messageXmlArr)){
            foreach ($messageXmlArr as $messageXml) {
                $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
                $xmlData .= '<Request>';
                $xmlData .=     '<Product>';
                $xmlData .=         '<Skus>';
                $xmlData .= $messageXml;
                $xmlData .=         '</Skus>';
                $xmlData .=     '</Product>';
                $xmlData .= '</Request>';
                $returnXmlData []= $xmlData;
            }
            return $returnXmlData;
        }
    }

    private function getUpdatePriceQuantityXmlData($processStatusProduct)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<Request>';
        foreach ($processStatusProduct as $pendingSku) {
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
        return $xmlData;
    }

    private function getNewApiMattleUpdateQuantityXmlData($inventoryGroup,$limitMaxNumber)
    {
        $messageXmlArr = array();
        $newInventoryGroup = array_chunk($inventoryGroup->toArray(),$limitMaxNumber,true);
        foreach ($newInventoryGroup as $limitInventoryGroup) {
            $messageXml = null;
            foreach ($limitInventoryGroup as $platformMarketInventory) {
                $messageDom = '<Sku>';
                $messageDom .= '<SellerSku>'.$platformMarketInventory["marketplace_sku"].'</SellerSku>';
                $messageDom .= '<Quantity>'.$platformMarketInventory["inventory"].'</Quantity>';
                $messageDom .= '</Sku>';
                $messageXml .= $messageDom;
            }
            $messageXmlArr[] = $messageXml;
        }
        if(!empty($messageXmlArr)){
            foreach ($messageXmlArr as $messageXml) {
                $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
                $xmlData .= '<Request>';
                $xmlData .=     '<Product>';
                $xmlData .=         '<Skus>';
                $xmlData .= $messageXml;
                $xmlData .=         '</Skus>';
                $xmlData .=     '</Product>';
                $xmlData .= '</Request>';
                $returnXmlData[] = $xmlData;
            }
            return $returnXmlData;
        }
    }

    private function getMattleUpdateQuantityXmlData($inventoryGroup)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlData .= '<Request>';
        foreach ($inventoryGroup as $platformMarketInventory) {
            $messageDom = '<Product>';
            $messageDom .= '<SellerSku>'.$platformMarketInventory->marketplace_sku.'</SellerSku>';
            $messageDom .= '<Quantity>'.$platformMarketInventory->inventory.'</Quantity>';
            $messageDom .= '</Product>';
            $xmlData .= $messageDom;
        }
        $xmlData .= '</Request>';
        return $xmlData;
    }

    public function updatePlatformMarketProductStatus($pendingProducts,$errorSku = array(),$processStatus = null)
    {
        foreach ($pendingProducts as $pendingProduct) {
            if(empty($errorSku) || !in_array($pendingProduct->marketplace_sku,$errorSku)){
                if($processStatus){
                    $this->updatePendingProductProcessStatusBySku($pendingProduct,$processStatus);
                }else{
                    $pendingProduct->update_status = 2;
                    $pendingProduct->save();
                }
            }
        }
    }

}
