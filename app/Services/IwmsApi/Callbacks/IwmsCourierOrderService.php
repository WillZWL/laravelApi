<?php

namespace App\Services\IwmsApi\Callbacks;

use App;
use App\Models\So;
use App\Models\IwmsCourierOrderLog;

class IwmsCourierOrderService
{
    public function __construct()
    {
    }

    public function createCourierOrder($postMessage)
    {
        $batchObject = $this->saveFeedRequestAndGetBatchObject($postMessage);
        if(!empty($batchObject)){
            $batchObject->status = "C";
            $batchObject->save();
            foreach ($postMessage->responseMessage as $key => $responseMessage) {
                if($key === "success"){
                    $this->updateIwmCallbackOrderSuccess($responseMessage, $batchObject->id);
                }else if ($key === "failed"){
                    $this->updateIwmCallbackOrderFaild($responseMessage, $batchObject->id);
                }
            }
        }
    }

    public function cancelCourierOrder($postMessage)
    {
    
    }   

    public function updateIwmCallbackOrderSuccess($responseMessage, $batchId)
    {
        $this->updateEsgOrderWaybillSataus($responseMessage, "2");
        $this->saveWaybillToPickListFolder($responseMessage);
        $this->updateIwmsCourierOrderSuccess($responseMessage, $batchId);
        $this->sendMsgCreateCourierOrderReport($responseMessage);
    }   

    private function updateIwmCallbackOrderFaild($responseMessage, $batchId)
    {
        $this->updateEsgOrderWaybillSataus($responseMessage, "3");
        $this->updateIwmsCourierOrderSuccess($responseMessage, $batchId);
        $this->sendMsgCreateCourierErrorEmail($responseMessage, $batchId);
    }

    private function updateEsgOrderWaybillSataus($responseMessage, $waybillStatus)
    {
        $soNoList = array();
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                $soNoList[] = $value->merchant_order_id;
            }
        }
        if(!empty($soNoList)){
            So::whereIn("so_no", $soNoList)
            ->update(array("waybill_status", $waybillStatus));
        }
    }

    private function saveWaybillToPickListFolder($responseMessage)
    {
        $pickListNo = "picklist-no";
        $filePath = $this->getCourierPickListFilePath($pickListNo);
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                $waybillLabel= file_get_contents($value->waybill_url);
                $file = $filePath.$value->merchant_order_id.'.pdf';
                file_put_contents($file, $waybillLabel);
            }
        }
    }

    public function getCourierPickListFilePath($pickListNo)
    {
        $filePath = \Storage::disk('pickList')->getDriver()->getAdapter()->getPathPrefix().$pickListNo."/AWB/";
        if (!file_exists($filePath)) {
            mkdir($filePath, 0755, true);
        }
        return $filePath;
    }

    private function updateIwmsCourierOrderSuccess($responseMessage, $batchId)
    {
        $soNoList = array();
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                IwmsCourierOrderLog::where("reference_no",$value->merchant_order_id)
                ->where("batch_id",$batchId)
                ->update(array("status" => 1)); 
            }
        }    
    }
    
    private function updateIwmsCourierOrderFaild($responseMessage, $batchId)
    {
        $soNoList = array();
        foreach ($responseMessage as $value) {
            if(!empty($value->merchant_order_id)){
                IwmsCourierOrderLog::where("reference_no",$value->merchant_order_id)
                ->where("batch_id",$batchId)
                ->update(array("status" => -1, "response_message" => $value->remark)); 
            }
        }    
    }

    public function sendMsgCreateCourierOrderReport($successResponseMessage)
    {
        
    }

    public function sendMsgCreateCourierErrorEmail($faildResponseMessage,$batchId)
    {
        $subject = "Create Courier Order Failed,Please Check Error";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        foreach ($faildResponseMessage as $value) {
            $msg .= "Order ID: $value->merchant_order_id, Error Message: $value->error_remark\r\n";
        }
        if($msg){
            $msg .= "\r\n";
            $this->_sendEmail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

}