<?php

namespace App\Services\IwmsApi;

use Illuminate\Http\Request;
use App\Models\So;
use App\Models\SoShipment;
use App\Models\InvMovement;
use App\Models\IwmsFeedRequest;
use App\Models\BatchRequest;
use App\Models\IwmsDeliveryOrderLog;

class IwmsCallbackApiService
{
    private $callbackToken = "esg-iwms-123456";
    use IwmsBaseService;

    public function __construct()
    {
    }

    public function valid(Request $request)
    {
        $echoStr = $request->input("echostr");
        $signature = $request->input("signature");
        $timestamp = $request->input("timestamp");
        $nonce = $request->input("nonce");
        $tmpArr = array($this->callbackToken, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return $echoStr;
        }
    }

    public function responseMsg(Request $request)
    {
        $echoStr = $request->input("echostr");
        $postContent = $request->getContent();
        //extract post data
        if (!empty($postContent)){
            $postMessage = json_decode($postContent);
            //run the own program jobs
            $this->responseMsgAction($postMessage);
            $responseMsg["signature"] = $this->checkSignature($postMessage,$echoStr);
            return $responseMsg;
        }
    }

    public function responseMsgAction($postMessage)
    {
        if($postMessage->action == "orderCreate"){
            IwmsFeedRequest::where("iwms_request_id", $postMessage->request_id)->update(array("status"=> "1","response_log" => json_encode($postMessage->responseMessage)));
             $batchObject = BatchRequest::where("iwms_request_id", $value->request_id)->first();
             if(!empty($batchObject)){
                $batchObject->status = "C";
                $batchObject->save();
            }
            foreach ($postMessage->responseMessage as $responseMessage) {
                if(isset($responseMessage["success"]) && !empty($responseMessage["success"])){
                    foreach ($responseMessage["success"] as $value) {
                        $this->updateEsgToShipOrderStatusToDispatch($value->merchant_order_id, $batchObject->id);
                    }
                    $this->sendMsgCreateDeliveryOrderReport($responseMessage["success"]);
                }
               if(isset($responseMessage["failed"]) && !empty($responseMessage["failed"])){
                    $this->sendMsgCreateDeliveryErrorEmail($responseMessage["success"]);
                }
            }
        }
    }
        
    public function checkSignature($postMessage,$echoStr)
    {
        $signatureArr = array();
        foreach ($postMessage->responseMessage as $value) {
            if(isset($value->order_code)){
                $signatureArr[] = $value->order_code;
            }else if(isset($value->receiving_code)){
                $signatureArr[] = $value->receiving_code;
            }
        }
        $signature = implode($signatureArr);
        return base64_encode($this->callbackToken.$signature.$echoStr);
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
            $cellData[] = array('Business Type', 'Merchant', 'Platform', 'Order ID', 'DELIVERY TYPE ID', 'Country', 'Battery Type', 'Rec. Courier', '4PX OMS delivery order ID', 'Pass to 4PX courier');
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

    private function updateEsgToShipOrderStatusToDispatch($esgSoNo, $batchId)
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
                    ->update(array("status" => 1));
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

    public function sendMsgCreateDeliveryErrorEmail($faildResponseMessage)
    {
        $subject = "Create OMS Delivery Order Failed,Please Check Error";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        foreach ($faildResponseMessage as $value) {
            $msg .= "Order ID: $value->merchant_order_id, Error Message: $value->error_remark\r\n";
        }
        if($msg){
            $msg .= "\r\n";
            mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }
}

