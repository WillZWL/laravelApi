<?php 

namespace App\Services;

use App\Models\PlatformMarketProductFeed;

/**
* 
*/
trait ApiBaseProductTraitService  
{
    use ApiPlatformTraitService;
    public function updatePendingProductProcessStatus($processStatusProduct,$processStatus)
    {
        if ($processStatus == PlatformMarketConstService::PENDING_PRICE) {
            $processStatusProduct->transform(function ($pendingSku) {
                if($pendingSku){
                   $pendingSku->process_status ^= PlatformMarketConstService::PENDING_PRICE;
                    $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_PRICE;
                    $pendingSku->save(); 
                }
            });
        }
        if ($processStatus == PlatformMarketConstService::PENDING_INVENTORY) {
            $processStatusProduct->transform(function ($pendingSku) {
                if($pendingSku){
                    $pendingSku->process_status ^= PlatformMarketConstService::PENDING_INVENTORY;
                    $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_INVENTORY;
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
            $pendingSku->save();
        }
        if ($processStatus == PlatformMarketConstService::PENDING_INVENTORY) {
            $pendingSku->process_status ^= PlatformMarketConstService::PENDING_INVENTORY;
            $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_INVENTORY;
            $pendingSku->save();
        }
        $pendingPriceAndInventory = PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
        if ($processStatus == $pendingPriceAndInventory) {
            $pendingSku->process_status |= PlatformMarketConstService::PENDING_PRICE | PlatformMarketConstService::PENDING_INVENTORY;
            $pendingSku->process_status ^= PlatformMarketConstService::PENDING_PRICE ^ PlatformMarketConstService::PENDING_INVENTORY;
            $pendingSku->process_status |= PlatformMarketConstService::COMPLETE_PRICE | PlatformMarketConstService::COMPLETE_INVENTORY;
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
    }
}