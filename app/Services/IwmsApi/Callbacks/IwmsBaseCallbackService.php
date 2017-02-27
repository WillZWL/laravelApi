<?php

namespace App\Services\IwmsApi\Callbacks;

use App\Models\IwmsFeedRequest;
use App\Models\BatchRequest;
use App\Models\SoAllocate;

class IwmsBaseCallbackService
{
    public function __construct()
    {
    }

    public function saveFeedRequestAndGetBatchObject($postMessage)
    {
        IwmsFeedRequest::where("iwms_request_id", $postMessage->request_id)->update([
            "status"=> "1",
            "response_log" => json_encode($postMessage->responseMessage)
        ]);
        $batchObject = BatchRequest::where("iwms_request_id", $postMessage->request_id)->first();
        return $batchObject;
    }

    public function getSoAllocatePickListNo($esgOrderNo)
    {
        $soAllocation = SoAllocate::where("so_no", $esgOrderNo)
                    ->first();
        if(!empty($soAllocation)){
            return $soAllocation->picklist_no;
        }
    }

}