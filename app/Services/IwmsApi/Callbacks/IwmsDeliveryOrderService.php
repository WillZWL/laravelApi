<?php

namespace App\Services\IwmsApi\Callbacks;

use App;
use App\Models\So;
use App\Models\SoAllocate;
use App\Models\SoShipment;
use App\Models\SoItemDetail;
use App\Models\InvMovement;
use App\Models\IwmsDeliveryOrderLog;
use App\Models\IwmsFeedRequest;

class IwmsDeliveryOrderService extends IwmsBaseCallbackService
{
    use \App\Services\IwmsApi\IwmsBaseService;
    public function __construct()
    {

    }

    public function deliveryOrderCreate($postMessage)
    {
        $batchObject = $this->saveFeedRequestAndGetBatchObject($postMessage);
        if(!empty($batchObject)){
            $batchObject->status = "C";
            $batchObject->save();
            foreach ($postMessage->responseMessage as $key => $responseMessage) {
                if($key === "success"){
                    foreach ($responseMessage as $value) {
                        $this->updateEsgToShipOrderStatusToDispatch($value->merchant_order_id, $value->order_code, $batchObject->id);
                    }
                    $this->sendMsgCreateDeliveryOrderReport($responseMessage);
                }
               if($key === "failed"){
                    $this->sendMsgCreateDeliveryErrorEmail($responseMessage, $batchObject->id);
                }
            }
        }
    }

    public function cancelDeliveryOrder($postMessage)
    {
        $batchObject = BatchRequest::where("iwms_request_id", $postMessage->request_id)->first();
        if(!empty($batchObject)){
            $batchObject->status = "C";
            $batchObject->save();
            foreach ($postMessage->responseMessage as $key => $responseMessage) {
                if($key === "success"){
                    foreach ($responseMessage as $value) {
                        $this->updateEsgDispatchOrderStatusToToShip($value->merchant_order_id, $value->order_code);
                    }
                    $subject = "Cancel OMS Delivery Order Success.";
                    $this->sendMsgCancelDeliveryOrderEmail($subject, $responseMessage);
                    return true;
                }
                if($key === "failed"){
                    $subject = "Cancel OMS Delivery Order Failed,Please Check Error";
                    $this->sendMsgCancelDeliveryOrderEmail($subject, $responseMessage);
                }
            }
        }
    }

    private function updateEsgToShipOrderStatusToDispatch($esgSoNo, $wmsOrderCode, $batchId)
    {
        $esgOrder = So::where("so_no", $esgSoNo)
                        ->with("sellingPlatform")
                        ->first();
        if(empty($esgOrder)){
            return false;
        }
        IwmsDeliveryOrderLog::where("platform_order_id",$esgOrder->platform_order_id)
                    ->where("batch_id", $batchId)
                    ->where("status", 0)
                    ->update(array("wms_order_code" => $wmsOrderCode, "status" => 1));
        $soShipment = $this->createEsgSoShipment($esgOrder);
        if(!empty($soShipment)){
            foreach ($esgOrder->soAllocate as $soAllocate) {
                if($soAllocate->status != 1){
                    continue;
                }
                $invMovement = InvMovement::where("ship_ref", $soAllocate->id)
                    ->where("status", "AL")
                    ->first();
                if(!empty($invMovement)){
                    $invMovement->ship_ref = $soShipment->sh_no;
                    $invMovement->status = "OT";
                    $invMovement->save();
                    $soAllocate->status = 2;
                    $soAllocate->sh_no = $soShipment->sh_no;
                    $soAllocate->save();
                }
            }
        }
    }

    public function createEsgSoShipment($esgOrder)
    {
        $soShipment = SoShipment::where("sh_no", $esgOrder->so_no."-01")->first();
        if(!empty($soShipment)){
            return null;
        }else{
            $object['sh_no'] = $esgOrder->so_no."-01";
            $object['courier_id'] = $esgOrder->esg_quotation_courier_id;
            /*$object['first_tracking_no'] = ;
            $object['first_courier_id'] = ;
            $object['tracking_no'] = ;*/
            $object['status'] = 1;
            $soShipment = SoShipment::updateOrCreate(['sh_no' => $object['sh_no']],$object);
            return $soShipment;
        }
    }

    public function sendMsgCreateDeliveryOrderReport($successResponseMessage)
    {
        $cellData = $this->getMsgCreateDeliveryOrderReport($successResponseMessage);
        $filePath = \Storage::disk('iwms')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."orderCreate/";
        $fileName = "deliveryOrderDetail-".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "OMS Create Delivery Order Report!";
                $attachment = array("path" => $orderPath,"file_name"=>$fileName.".xlsx");
                $this->sendAttachmentMail('privatelabel-log@eservicesgroup.com',$subject,$attachment);
            }
        }
    }

    public function getMsgCreateDeliveryOrderReport($successResponseMessage)
    {
        if(!empty($successResponseMessage)){
            $cellData[] = array('Business Type', 'Merchant', 'Platform', 'Platform Order No', 'Order ID', 'DELIVERY TYPE ID', 'Country', 'Battery Type', 'Rec. Courier', '4PX OMS delivery order ID', 'Pass to 4PX courier');
            foreach ($successResponseMessage as $value) {
                $esgOrder = So::where("so_no",$value->merchant_order_id)
                        ->with("sellingPlatform")
                        ->first();
                if(!empty($esgOrder)){
                    $builtIn = $esgOrder->hasInternalBattery();
                    $external = $esgOrder->hasExternalBattery();
                    $batteryType = "No Battery";
                    if($builtIn){
                        $batteryType = "Built In";
                    }else if($external){
                        $batteryType = "External";
                    }
                    $cellRow = array(
                        'business_type' => $esgOrder->sellingPlatform->type,
                        'merchant' => $esgOrder->sellingPlatform->merchant_id,
                        'platform' => $esgOrder->platform_id,
                        'platform_order_no' => $esgOrder->platform_order_id,
                        'order_id' => $value->merchant_order_id,
                        'delivery_type_id' => $esgOrder->delivery_type_id,
                        'country' => $value->country,
                        'battery_type' => $batteryType,
                        're_courier' => $esgOrder->courierInfo->courier_name,
                        'wms_order_code' => $value->order_code,
                        'wms_courier' => $value->iwms_courier,
                    );
                    $cellData[] = $cellRow;
                }

            }
            return $cellData;
        }
        return null;
    }

    private function getCreateDeliveryOrderReport($iwmsRequestIds)
    {
        $requestBody = array("request_id" => $iwmsRequestIds);
        $responseJson = $this->curlIwmsApi('wms/get-delivery-order-report', $requestBody);
        if(!empty($responseJson)){
            $responseData = json_decode($responseJson);
            $cellData[] = array('Business Type', 'Merchant', 'Platform', 'Order ID', 'DELIVERY TYPE ID', 'Country', 'Battery Type', 'Rec. Courier', '4PX OMS delivery order ID', 'Pass to 4PX courier');
            foreach ($responseData as $requestId => $deliveryOrders) {
                foreach ($deliveryOrders as $value) {
                    $esgOrder = So::where("so_no",$value->merchant_order_id)
                            ->with("sellingPlatform")
                            ->first();
                    if(!empty($esgOrder)){
                       $cellRow = array(
                            'business_type' => $value->business_type,
                            'merchant' => $esgOrder->sellingPlatform->merchant_id,
                            'platform' => $esgOrder->platform_id,
                            'order_id' => $value->reference_no,
                            'delivery_type_id' => $esgOrder->delivery_type_id,
                            'country' => $value->country,
                            'battery_type' => "",
                            're_courier' => $esgOrder->recommend_courier_id,
                            'wms_order_code' => $value->wms_order_code,
                            'wms_courier' => $value->iwms_courier,
                        );
                        $cellData[] = $cellRow;
                    }
                }
                IwmsFeedRequest::where("iwms_request_id",$requestId)->update(array("status"=> "1"));
            }
            return $cellData;
        }
        return null;
    }

    public function sendMsgCreateDeliveryErrorEmail($faildResponseMessage,$batchId)
    {
        $subject = "Create OMS Delivery Order Failed,Please Check Error";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        foreach ($faildResponseMessage as $value) {
            $msg .= "Order ID: $value->merchant_order_id, Error Message: $value->error_remark\r\n";
            IwmsDeliveryOrderLog::where("platform_order_id",$value->merchant_order_id)
                    ->where("batch_id", $batchId)
                    ->update(array("response_message" => $value->error_remark));
        }
        if($msg){
            $msg .= "\r\n";
            $this->_sendEmail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

    private function sendMsgCancelDeliveryOrderEmail($subject, $responseMessage)
    {
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        foreach ($responseMessage as $value) {
            $msg .= "Order ID:".$value->merchant_order_id."\r\n";
        }
        if($msg){
            mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

    private function updateEsgDispatchOrderStatusToToShip($esgSoNo, $wmsOrderCode)
    {
        $esgOrder = So::where("so_no", $esgSoNo)
                        ->with("sellingPlatform")
                        ->with("soAllocate")
                        ->first();
        if(empty($esgOrder)){
            return false;
        }
        IwmsDeliveryOrderLog::where("reference_no",$esgOrder->so_no)
                    ->where("wms_order_code",$wmsOrderCode)
                    ->update(array("status" => -1));
        if(!empty($esgOrder->soAllocate)){
            foreach ($esgOrder->soAllocate as $soAllocate) {
                if($soAllocate->status != 2){
                    continue;
                }
                $invMovement = InvMovement::where("ship_ref", $soAllocate->sh_no)
                    ->where("status", "OT")
                    ->first();
                if(!empty($invMovement)){
                    //need remove soAllocate can delete shipment
                    $object = array();
                    $object["so_no"] = $soAllocate->so_no;
                    $object["line_no"] = $soAllocate->line_no;
                    $object["item_sku"] = $soAllocate->item_sku;
                    $object["qty"] = $soAllocate->qty;
                    $object["warehouse_id"] = $soAllocate->warehouse_id;
                    $object["status"] = 1;
                    $newSoAllocate = SoAllocate::firstOrCreate($object);
                    if(!empty($newSoAllocate)){
                        $invMovement->ship_ref = $newSoAllocate->id;
                        $invMovement->status = "AL";
                        $invMovement->save();
                        $soShipment = $soAllocate->soShipment;
                        $soAllocate->delete();
                        $soShipment->delete();
                    }
                }
            }
            $esgOrder->modify_on = date("Y-m-d H:i:s");
            $esgOrder->save();
        }
    }

}