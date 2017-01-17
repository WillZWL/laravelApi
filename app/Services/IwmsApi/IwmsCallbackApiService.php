<?php

namespace App\Services\IwmsApi;

use Illuminate\Http\Request;
use App\Models\So;
use App\Models\SoShipment;
use App\Models\SoItemDetail;
use App\Models\InvMovement;
use App\Models\IwmsFeedRequest;
use App\Models\BatchRequest;
use App\Models\IwmsDeliveryOrderLog;

use App\Services\ShippingService;
use App\Repository\AcceleratorShippingRepository;
use App\Repository\MarketplaceProductRepository;
use App\Models\ExchangeRate;

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
            $responseData = $this->responseMsgAction($postMessage);
            if (isset($responseData)) {
                $responseMsg["responseData"] = $responseData;
            }
            $responseMsg["signature"] = $this->checkSignature($postMessage,$echoStr);
            return $responseMsg;
        }
    }

    public function responseMsgAction($postMessage)
    {
        switch ($postMessage->action) {
            case 'orderCreate':
                return $this->deliveryOrderCreate($postMessage);
                break;
            case 'confirmShipped':
                return $this->deliveryConfirmShipped($postMessage);
                break;
            case 'cancelDelivery':
                return $this->cancelDeliveryOrder($postMessage);
                break;

            default:
                break;
        }
    }

    public function deliveryConfirmShipped($postMessage)
    {
        $shippedCollection = [];
        $responseMessage = $postMessage->responseMessage;
        if (isset($responseMessage)) {
            foreach ($responseMessage as $shippedOrder) {
                $shipped = $this->confirmShippedEsgOrder($shippedOrder);
                $shippedCollection[$shippedOrder->reference_no] = $shipped;
            }
            $this->sendDeliveryOrderShippedReport($responseMessage, $shippedCollection);
        }

        return $shippedCollection;
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
                        $this->updateEsgDispatchOrderStatusToToShip($value->merchant_order_id);
                    }
                    $subject = "Cancel OMS Delivery Order Success,Please Check Error";
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

    private function updateEsgDispatchOrderStatusToToShip($esgSoNo)
    {
        $esgOrder = So::where("so_no", $esgSoNo)
                        ->with("sellingPlatform")
                        ->with("soAllocate")
                        ->first(); 
        if(empty($esgOrder)){
            return false;
        }
        IwmsDeliveryOrderLog::where("reference_no",$esgOrder->so_no)
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
            $esgOrder->modify_on = date("Y-m-d H:i:s");
            $esgOrder->save();
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

    public function sendDeliveryOrderShippedReport($responseMessage, $shippedCollection)
    {
        $cellData = $this->geMsgShippedReport($responseMessage, $shippedCollection);
        $filePath = \Storage::disk('iwms')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."ConfirmDispatch/";
        $fileName = "deliveryShipment-".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "[ESG]By IWMS Confirm Dispatch Collection Report!";
                $attachment = array("path" => $orderPath,"file_name"=>$fileName.".xlsx");
                $this->sendAttachmentMail('privatelabel-log@eservicesgroup.com',$subject,$attachment);
            }
        }
    }

    public function geMsgShippedReport($responseMessage, $shippedCollection)
    {
        if(!empty($responseMessage)){
            $cellData[] = [
                'Request Id', 
                'Order No.', 
                'Platform Order No.', 
                'Ship Date', 
                'Tracking No.', 
                'Weight Predict', 
                'Weight Actual', 
                'Billing Weight Actual', 
                'Confirm Dispatch', 
            ];
            foreach ($responseMessage as $shippedOrder) {
                $esgSoNo = $shippedOrder->reference_no;
                if (isset($shippedCollection[$esgSoNo])
                    && isset($shippedOrder->tracking_no)
                    && $shippedCollection[$esgSoNo] === true
                ) {
                    $confirmDispatch = "Success";
                } else {
                    if (empty($shippedOrder->tracking_no)) {
                        $confirmDispatch = "Failed, No tracking No.";
                    } else {
                        $confirmDispatch = "Failed";
                    }
                }
                $cellData[] = array(
                    'request_id' => $shippedOrder->request_id,
                    'reference_no' => $esgSoNo,
                    'marketplace_reference_no' => $shippedOrder->marketplace_reference_no,
                    'ship_date' => $shippedOrder->ship_date,
                    'tracking_no' => $shippedOrder->tracking_no,
                    'weight_predict' => $shippedOrder->weight_predict,
                    'weight_actual' => $shippedOrder->weight_actual,
                    'weight_charge' => $shippedOrder->weight_charge,
                    'confirm_dispatch' => $confirmDispatch,
                );
            }
            return $cellData;
        }
        return null;
    }

    public function confirmShippedEsgOrder($shippedOrder)
    {
        try {
            if ($shippedOrder->tracking_no 
                && $esgOrder = So::UnshippedOrder()->where("so_no", $shippedOrder->reference_no)->first()
            ) {
                if ($firstSoAllocate =  $esgOrder->soAllocate->where('status', 2)->first()) {
                    $soShipment = $firstSoAllocate->soShipment;
                    $soShipment->status = 2;
                    $soShipment->tracking_no = $shippedOrder->tracking_no;
                    $soShipment->save();

                    if ($soAllocates = $esgOrder->soAllocate) {
                        foreach ($soAllocates as $soAllocate) {
                            $soAllocate->status = 3;
                            $soAllocate->save();
                            SoItemDetail::where('so_no', $soAllocate->so_no)
                                ->where('line_no', $soAllocate->line_no)
                                ->update(['status' => 1]);
                        }
                    }

                    $esgOrder->status = 6;
                    $esgOrder->actual_weight = $shippedOrder->weight_charge;
                    $esgOrder->dispatch_date = $shippedOrder->ship_date ? $shippedOrder->ship_date : date("Y-m-d H:i:s");
                    $esgOrder->save();
                    $this->setRealDeliveryCost($esgOrder);
                    return true;
                }
            }
        } catch (Exception $e) {
            $to = "privatelabel-log@eservicesgroup.com";
            $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
            $subject = "[ESG] Alert, Confirm Shipped Exception, ESG so_no: ". $shippedOrder->reference_no;
            $message = "Error: ". $e->getMessage();
            $this->_sendEmail($to, $subject, $message, $header);
        }

        return false;
    }

    public function setRealDeliveryCost($esgOrder)
    {
        $this->shippingService = new ShippingService(
            new AcceleratorShippingRepository, 
            new MarketplaceProductRepository
        );
        $deliveryInfo = $this->shippingService->orderDeliveryCost($esgOrder->so_no);
        if (!isset($deliveryInfo['error']) 
            && isset($deliveryInfo['delivery_cost'])
        ) {
            $rate = ExchangeRate::getRate($deliveryInfo['currency_id'], $esgOrder->currency_id);
            $esgOrder->real_delivery_cost = $deliveryInfo['delivery_cost'] * $rate;
            $esgOrder->final_surcharge = $deliveryInfo['surcharge'];
            $esgOrder->save();
        }
    }

    public function deliveryOrderCreate($postMessage)
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
                    foreach ($responseMessage as $value) {
                        $this->updateEsgToShipOrderStatusToDispatch($value->merchant_order_id, $batchObject->id);
                    }
                    $this->sendMsgCreateDeliveryOrderReport($responseMessage);
                }
               if($key === "failed"){
                    $this->sendMsgCreateDeliveryErrorEmail($responseMessage, $batchObject->id);
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

    private function _sendEmail($to, $subject, $message, $header)
    {
        mail("{$to}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $message, $header);
    }

}

