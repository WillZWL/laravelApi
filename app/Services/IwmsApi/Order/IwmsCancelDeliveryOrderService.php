<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsDeliveryOrderLog;

class IwmsCancelDeliveryOrderService
{
    private $warehouseIds = null;
    private $message = null;
    private $wmsPlatform = null;

    use \App\Services\IwmsApi\IwmsBaseService;
    
    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getDeliveryCancelRequest($esgOrderSoList)
    {
        $deliveryCancelRequest = null;
        $batchRequest = $this->getDeliveryCancelRequestBatch($esgOrderSoList);
        return empty($batchRequest) ? null : $batchRequest;
    }

    public function getDeliveryCancelRequestBatch($esgOrderSoList)
    {
        $esgAllocateOrder = null; $merchantId = "ESG";
        $iwmsDeliveryOrderLogs = $this->getIwmsDeliveryEsgOrderLogs($esgOrderSoList);
        if(!$iwmsDeliveryOrderLogs->isEmpty()){
            $batchRequest = $this->getNewBatchId("CANCEL_DELIVERY",$this->wmsPlatform,$merchantId);
            foreach ($iwmsDeliveryOrderLogs as $iwmsDeliveryOrderLog) {
                $deliveryOrderObj = array(
                    "wms_platform" => $iwmsDeliveryOrderLog->wms_platform,
                    "merchant_id" => $iwmsDeliveryOrderLog->merchant_id,
                    "sub_merchant_id" => $iwmsDeliveryOrderLog->sub_merchant_id,
                    "reference_no" => $iwmsDeliveryOrderLog->reference_no,
                );
                $cancelDeliveryOrderObj[] = $deliveryOrderObj;
            }
            $batchRequest->request_log = json_encode($cancelDeliveryOrderObj);
            $batchRequest->save();
            return $batchRequest;
        }
    }

    public function responseMsgCancelAction($batchRequest, $postContent)
    {
        $postMessage = json_decode($postContent); 
        $msg = null;
        if(isset($postMessage->request_id) && !empty($batchObject)){
            $batchObject->iwms_request_id = $postMessage->request_id;
            $batchObject->response_log = $postMessage->response_log;
            $batchObject->save();
            foreach ($batchObject->response_log as $responseLog) {
                if(isset($responseLog->error)){
                    $msg .= "Order ID:".$responseLog->merchant_order_id."
                    error:".$responseLog->error."\r\n";
                }
                if($msg){
                    $subject = "Cancel OMS Delivery Order Failed,Please Check Error";
                    $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
                    $alertEmail = "privatelabel-log@eservicesgroup.com";
                    mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
                }
            }
        }
    }

    public function getIwmsDeliveryEsgOrderLogs($esgOrderSoList)
    {
       return IwmsDeliveryOrderLog::whereIn('reference_no', $esgOrderSoList)
                    ->where("status", 1)
                    ->get();
    }

}