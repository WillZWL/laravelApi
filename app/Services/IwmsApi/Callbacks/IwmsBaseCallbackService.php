<?php

namespace App\Services\IwmsApi\Callbacks;

use App\Models\IwmsFeedRequest;
use App\Models\BatchRequest;
use App\Models\So;

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

    public function getSoAllocatedPickListNo($esgOrderNo)
    {
        $esgOrder = So::where("so_no", $esgOrderNo)
                    ->first();
        if(!empty($esgOrderNo)){
            return $esgOrderNo->pick_list_no;
        }
    }

    public function _sendEmail($to, $subject, $message, $header)
    {
        mail("{$to}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $message, $header);
    }
}