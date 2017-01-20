<?php 

namespace App\Services;

use App\Models\PlatformMarketProductFeed;
use App\Models\MarketplaceSkuMapping;
use App\Models\WmsWarehouseMapping;
use App\Models\PlatformMarketFeedBatch;
use App\Models\PlatformMarketInventory;
use App\Models\Store;
use App\Models\PlatformMarketAttributeType;
/**
* 
*/
trait ApiBaseProductTraitService  
{
    use ApiPlatformTraitService;
    public $platformAcronym = array(
            'AZ' =>'AMAZON',
            'LZ' =>'LAZADA',
            'PM' => 'PRICEMINISTER',
            'FN' => 'FNAC',
            'QO' => 'QOO10',
            'NE' => 'NEWEGG',
            'TG' => 'TANGA',
        );
    
    public function updatePendingProductProcessStatus($processStatusProduct,$processStatus)
    {
        if ($processStatus == PlatformMarketConstService::PENDING_PRICE) {
            $processStatusProduct->transform(function ($pendingSku) {
                if($pendingSku){
                   $pendingSku->process_status ^= PlatformMarketConstService::PENDING_PRICE;
                    $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_PRICE;
                    $pendingSku->failed_times = 0;
                    $pendingSku->save(); 
                }
            });
        }
        if ($processStatus == PlatformMarketConstService::PENDING_INVENTORY) {
            $processStatusProduct->transform(function ($pendingSku) {
                if($pendingSku){
                    $pendingSku->process_status ^= PlatformMarketConstService::PENDING_INVENTORY;
                    $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_INVENTORY;
                    $pendingSku->failed_times = 0;
                    $pendingSku->save();
                }
            });
        }
        $pendingPriceAndInventory = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        if ($processStatus == $pendingPriceAndInventory) {
            $processStatusProduct->transform(function ($pendingSku) {
                //if the process_status is 27,the value will be 29 after update,
                // it will be repost next time
                if($pendingSku){
                    $pendingSku->process_status |= PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
                    $pendingSku->process_status ^= PlatformMarketConstService::PENDING_PRICE ^ PlatformMarketConstService::PENDING_INVENTORY;
                    $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_PRICE | PlatformMarketConstService::COMPLETE_INVENTORY;
                    $pendingSku->failed_times = 0;
                    $pendingSku->save();
                }
            });
        }
    }

    public function updatePendingProductProcessStatusBySku($pendingSku,$processStatus)
    {
        if ($processStatus == PlatformMarketConstService::PENDING_PRICE) {
            $pendingSku->process_status ^= PlatformMarketConstService::PENDING_PRICE;
            $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_PRICE;
            $pendingSku->failed_times = 0;
            $pendingSku->save();
        }
        if ($processStatus == PlatformMarketConstService::PENDING_INVENTORY) {
            $pendingSku->process_status ^= PlatformMarketConstService::PENDING_INVENTORY;
            $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_INVENTORY;
            $pendingSku->failed_times = 0;
            $pendingSku->save();
        }
        $pendingPriceAndInventory = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        if ($processStatus == $pendingPriceAndInventory) {
            $pendingSku->process_status |= PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
            $pendingSku->process_status ^= PlatformMarketConstService::PENDING_PRICE ^ PlatformMarketConstService::PENDING_INVENTORY;
            $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_PRICE | PlatformMarketConstService::COMPLETE_INVENTORY;
            $pendingSku->failed_times = 0;
            $pendingSku->save();
        }
    }

    public function createOrUpdatePlatformMarketProductFeed($storeName,$feedSubmissionId)
    {
        $object = array(
            "platform" => $storeName,
            "feed_type" => '_UPDATE_PRODUCT_QTY_AND_INVENTORY_DATA_',
            "feed_submission_id" => $feedSubmissionId,
            "feed_processing_status" => '_SUBMITTED_',
            );
        $platformMarketProductFeed = PlatformMarketProductFeed::updateOrCreate(
            [
                'platform' => $storeName,
                'feed_submission_id' => $feedSubmissionId,
            ],
            $object
        );
        return $platformMarketProductFeed;
    }

    public function confirmPlatformMarketInventoryStatus($productUpdateFeed,$errorSku = array())
    {
        if($productUpdateFeed->platformMarketFeedBatch){
            foreach ($productUpdateFeed->platformMarketFeedBatch as $platformMarketFeedBatch) {
                if(!in_array($platformMarketFeedBatch->marketplace_sku,$errorSku)){
                    if($platformMarketFeedBatch->fun_name == "mattle_update_inventory"){
                        PlatformMarketInventory::where('id',$platformMarketFeedBatch->update_id)->update(array('update_status' => "2"));
                    }else{
                        $marketplaceSkuMapping = MarketplaceSkuMapping::find($platformMarketFeedBatch->update_id);
                        $this->updatePendingProductProcessStatusBySku($marketplaceSkuMapping,$platformMarketFeedBatch->process_status);
                    }
                    $platformMarketFeedBatch->status = "C";
                }else{
                    $platformMarketFeedBatch->status = "F";
                }
                $platformMarketFeedBatch->save();
            }
        }
        if($errorSku){
            $productUpdateFeed->feed_processing_status = '_COMPLETE_WITH_ERROR_';
        }else{
            $productUpdateFeed->feed_processing_status = '_COMPLETE_';
        }
        $productUpdateFeed->save();
    }

    public function createOrUpdatePlatformMarketFeedBatch($functionName,$feedId,$updateId,$marketplaceSku,$processStatus = null)
    {
        $object = array(
            'fun_name' => $functionName,
            "feed_id" => $feedId,
            "update_id" => $updateId,
            "marketplace_sku" => $marketplaceSku,
            "process_status" => $processStatus,
            "status" => "N"
        );
        PlatformMarketFeedBatch::updateOrCreate(['marketplace_sku' => $marketplaceSku,'update_id' => $updateId,"status"=> "F"], $object);
    }

    public function getWmsWarehouseSkuOrderedList($warehouseOrderGroups)
    {
        $warehouseSkuOrderedList = array();
        foreach($warehouseOrderGroups as $warehouseId => $warehouseOrderGroup){
            $warehouseMapping = WmsWarehouseMapping::where("warehouse_id","=",$warehouseId)->first();
            $platformPrefix = strtoupper(substr($warehouseMapping->platform_id, 3, 2));
            $platformAcronym = strtoupper(substr($warehouseMapping->platform_id, 5, 2));
            $countryCode = strtoupper(substr($warehouseMapping->platform_id, -2));
            $marketplaceId = $platformPrefix.$this->platformAcronym[$platformAcronym];
            $marketplaceSkuList = MarketplaceSkuMapping::join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
                        ->join('brand', 'brand.id', '=', 'product.brand_id')
                        ->join('sku_mapping', 'sku_mapping.sku', '=', 'product.sku')
                        ->where("marketplace_id","=",$marketplaceId)
                        ->where("country_id","=",$countryCode)
                        ->select('marketplace_sku_mapping.sku','marketplace_sku_mapping.marketplace_sku','product.name as product_name','brand.brand_name','sku_mapping.ext_sku as master_sku')
                        ->get()
                        ->toArray();
            $platformSkuOrderedList = $this->getPlatformSkuOrderedList($warehouseOrderGroup,$marketplaceSkuList);
            $warehouseSkuOrderedList[$warehouseId] = $platformSkuOrderedList;
        }
        return $warehouseSkuOrderedList;
    }

    public function getPlatformMarketAttributeOptions($storeName,$product)
    {
        $store = $this->getPlatformStore($storeName);
        $attributeTypes = PlatformMarketAttributeType::where("store_id",$store->id)
                    ->where("category_id",$product->platmarket_cat_id)
                    ->with("PlatformMarketAttributeOptions")
                    ->get();       
        foreach ($attributeTypes as $attributeType) {
           $attributeOptions[$attributeType->attribute_type_name] = $attributeType->platformMarketAttributeOptions->pluck("value","meta_key")->toArray();
        }
        return $attributeOptions;
    }

}