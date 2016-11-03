<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\MarketplaceSkuMapping;
use App\Models\MpControl;
use Config;

//use fnac api package
use App\Repository\FnacMws\FnacProductList;
use App\Repository\FnacMws\FnacProductUpdate;

class ApiFnacProductService implements ApiPlatformProductInterface
{
    use ApiBaseProductTraitService;
    public function __construct()
    {
        $this->stores =  Config::get('fnac-mws.store');
    }

    public function getPlatformId()
    {
        return 'Fnac';
    }

    public function getProductList($storeName)
    {
        $this->fnacProductList = new FnacProductList($storeName);

        $orginProductList = $this->fnacProductList->fetchProductList();

        $this->saveDataToFile(serialize($orginProductList), 'getProductList');

        return $orginProductList;
    }

    public function submitProductPriceAndInventory($storeName)
    {
        $processStatus = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        $pendingProducts = MarketplaceSkuMapping::ProcessStatusProduct($storeName,$processStatus);  
        if(!$pendingProducts->isEmpty()){
            $this->fnacProductUpdate = new FnacProductUpdate($storeName);
            $xmlData = $this->fnacProductUpdate->setRequestUpdateOfferXml($pendingProducts);
            $this->saveDataToFile(serialize($xmlData), 'pendingPriceAndInventory');
            $responseBatchData = $this->fnacProductUpdate->requestFnacUpdateOffer();
            $this->saveDataToFile(serialize($responseBatchData), 'responseBatchPriceAndInventory');
            if ($responseBatchData['@attributes']['status'] != 'FATAL') {
                $platformMarketProductFeed = $this->createOrUpdatePlatformMarketProductFeed($storeName,$responseBatchData['batch_id']);
                if($platformMarketProductFeed->id){
                    foreach ($pendingProducts as $pendingProduct) {
                       $this->createOrUpdatePlatformMarketFeedBatch("update_inventory_and_price",$platformMarketProductFeed->id,$pendingProduct->id,$pendingProduct->marketplace_sku,$processStatus);
                    }
                }
            }
        }
    }

    public function getProductUpdateFeedBack($storeName,$productUpdateFeeds)
    {
        $this->fnacProductUpdate = new FnacProductUpdate($storeName);
        $message = null;
        foreach ($productUpdateFeeds as $productUpdateFeed) {
            $errorSku = array();
            $reports = $this->fnacProductUpdate->sendFnacBatchStatusRequest($productUpdateFeed->feed_submission_id);
            $this->saveDataToFile(serialize($reports), 'getProductUpdateFeedBack');
            if($reports){
                if(isset($reports['@attributes']) && $reports['@attributes']['status'] == 'ERROR'){
                    $message .= "Seller Sku ".$reports["offer_seller_id"]." error message: ".$reports["error"]."\r\n";
                    $errorSku[] = $report["offer_seller_id"];
                }else{
                    foreach ($reports as $report){
                       if($report['@attributes']['status'] == 'ERROR'){
                            $message .= "Seller Sku ".$report["offer_seller_id"]." error message: ".$report["error"]."\r\n";
                            $errorSku[] = $report["offer_seller_id"];
                       }
                    }
                }
                $this->confirmPlatformMarketInventoryStatus($productUpdateFeed,$errorSku);
            }
        }
        if($message){
            $alertEmail = $this->stores[$storeName]["userId"];
            $subject = $storeName." price or inventory update failed!";
            $this->sendMailMessage($alertEmail, $subject, $message);
        }
    }

    public function submitProductCreate($storeName,$productGroup)
    {

    }

    public function submitProductUpdate($storeName)
    {
        $this->runProductUpdate($storeName, 'pendingProduct');
    }
}
