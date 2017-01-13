<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsDeliveryOrderLog;
use Illuminate\Database\Eloquent\Collection;

class IwmsCancelDeliveryOrderService
{
    private $warehouseIds = null;
    private $message = null;
    use \App\Services\IwmsApi\IwmsBaseService;
    
    public function getDeliveryCancelRequest($esgOrderSoList)
    {
        $deliveryCancelRequest = null;
        $batchRequest = $this->getDeliveryCancelRequestBatch($esgOrderSoList);
        return (!empty($batchRequest)）? $batchRequest : null;
    }

    public function getDeliveryCancelRequestBatch($esgOrderSoList)
    {
        $esgAllocateOrder = null; $merchantId = "ESG";
        $esgOrders = $this->getIwmsDeliveryEsgOrders($esgOrderSoList);
        if(!$esgOrders->isEmpty()){
            $batchRequest = $this->getNewBatchId("CANCEL_DELIVERY",$this->wmsPlatform,$merchantId);
            foreach ($esgOrders as $esgOrder) {
                foreach ($esgOrder->iwmsDeliveryOrderLog as $iwmsDeliveryOrderLog) {
                    $deliveryOrderObj = array(
                        "wms_platform" => $this->wmsPlatform,
                        "iwms_order_code" => $iwmsWarehouseCode,
                        "reference_no" => $esgOrder->so_no,
                    );
                    $cancelDeliveryOrderObj[] = $deliveryOrderObj;
                }
            }
            $batchRequest->request_log = json_encode($cancelDeliveryOrderObj);
            $batchRequest->save();
            return $batchRequest;
        }
    }

    public function responseMsgCancelAction($postMessage)
    {
        IwmsFeedRequest::where("iwms_request_id", $postMessage->request_id)->update(array("status"=> "1","response_log" => json_encode($postMessage->responseMessage)));
        $batchObject = BatchRequest::where("iwms_request_id", $postMessage->request_id)->first();
        if(!empty($batchObject)){
            $batchObject->status = "C";
            $batchObject->save();
            foreach ($postMessage->responseMessage as $key => $responseMessage) {
                if($key === "success"){
                    foreach ($responseMessage as $value) {
                        $this->updateEsgDispatchOrderStatusToToShip($value->merchant_order_id, $batchObject->id);
                    }
                    $subject = "Cancel OMS Delivery Order Success,Please Check Error";
                    $this->sendMsgCancelDeliveryOrderEmail($subject, $responseMessage);
                }
               if($key === "failed"){
                    $subject = "Cancel OMS Delivery Order Failed,Please Check Error";
                    $this->sendMsgCancelDeliveryOrderEmail($subject, $responseMessage);
                }
            }
        }
    }

    public function getIwmsDeliveryEsgOrders($esgOrderSoList)
    {
        $deliveryEsgOrders = So::whereIn("so_no",$esgOrderSoList)
            ->whereHas('iwmsDeliveryOrderLog', function ($query) {
                $query->whereIn('reference_no', $esgOrderSoList)
                    ->where("status", 1)
            })
            ->with("soAllocate")
            ->with("client")
            ->with("soItem")
            ->get();
        return $deliveryEsgOrders;
    }

    private function updateEsgDispatchOrderStatusToToShip($esgSoNo, $batchId)
    {
        $esgOrder = So::where("so_no", $esgSoNo)
                        ->with("sellingPlatform")
                        ->with("soAllocate")
                        ->first(); 
        if(empty($esgOrder)){
            return false;
        }
        IwmsDeliveryOrderLog::where("platform_order_id",$esgOrder->platform_order_id)
                    ->where("batch_id", $batchId)
                    ->where("status", 1)
                    ->update(array("status" => -1));
        if(!empty($soShipment)){
            foreach ($esgOrder->soAllocate as $soAllocate) { 
                if($soAllocate->status != 2){
                    continue;
                }
                $soShipment = $soAllocate->soShipment;
                $invMovement = InvMovement::where("ship_ref", $soShipment->sh_no)
                    ->where("status", "OT")
                    ->first();
                if(!empty($invMovement)){
                    $invMovement->ship_ref = $soAllocate->id;
                    $invMovement->status = "AL";
                    $invMovement->save();
                    $soAllocate->status = 1;
                    $soAllocate->sh_no = "";
                    $soAllocate->save();
                }
                $soShipment->delete();
            }
        }
    }

    private function sendMsgCancelDeliveryOrderEmail($subject, $responseMessage)
    {
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        foreach ($responseMessage as $value) {
            $msg .= "Order ID: $value->merchant_order_id, Error Message: $value->error_remark\r\n";
        }
        if($msg){
            $msg .= "\r\n";
            mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

}