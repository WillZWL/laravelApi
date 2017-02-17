<?php

namespace App\Services\IwmsApi\Callback;

use App;
use App\Models\So;
use App\Models\IwmsFeedRequest;
use App\Models\BatchRequest;
use App\Models\IwmsCourierOrderLog;

class IwmsCourierOrderService
{
    public function __construct()
    {
    }

    public function createCourierOrder($postMessage)
    {
        IwmsFeedRequest::where("iwms_request_id", $postMessage->request_id)->update([
            "status"=> "1",
            "response_log" => json_encode($postMessage->responseMessage)
        ]);
        $batchObject = BatchRequest::where("iwms_request_id", $postMessage->request_id)->first();
        if(!empty($batchObject)){
            $batchObject->status = "C";
            $batchObject->save();
            foreach ($postMessage->responseMessage as $key => $responseMessage) {
                if($key === "success"){
                    $this->updateIwmCallbackOrderSataus($responseMessage, $batchObject->id, "2", "1");
                    $this->sendMsgCreateCourierOrderReport($responseMessage);
                }
               if($key === "failed"){
                    $this->updateIwmCallbackOrderSataus($responseMessage, $batchObject->id, "3", "-1")
                    $this->sendMsgCreateCourierErrorEmail($responseMessage, $batchObject->id);
                }
            }
        }
    }

    public function cancelCourierOrder($postMessage)
    {
       
    }

    private function updateIwmCallbackOrderSataus($responseMessage, $batchId, $waybillStatus, $courierOrderStatus)
    {
        $soNoList = array();
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                $soNoList[] = $value->merchant_order_id;
            }
        }
        $this->updateEsgOrderWaybillSataus($soNoList, $waybillStatus);
        $this->updateIwmsCourierOrderSataus($soNoList, $batchId, $courierOrderStatus);
    }

    private function updateEsgOrderWaybillSataus($soNoList, $status)
    {
        So::whereIn("so_no", $soNoList)
            ->update(array("waybill_status", $status));
    }

    private function updateIwmsCourierOrderSataus($soNoList, $batchId, $status)
    {
        IwmsCourierOrderLog::whereIn("reference_no",$soNoList)
                ->where("batch_id",$batchId)
                ->update(array("status" => $status));       
    }

}