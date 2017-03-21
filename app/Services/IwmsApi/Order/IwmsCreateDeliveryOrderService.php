<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsDeliveryOrderLog;
use App\Models\IwmsCourierOrderLog;
use App\Models\IwmsLgsOrderStatusLog;

use App;

use Illuminate\Database\Eloquent\Collection;

class IwmsCreateDeliveryOrderService
{
    private $fromData = null;
    private $toDate = null;
    private $warehouseIds = null;
    private $message = null;
    private $wmsPlatform = null;
    private $apiLazadaService = null;
    private $lgsCourier = array("4PX-PL-LGS");
    private $excludeMerchant = array("PREPD");

    use \App\Services\IwmsApi\IwmsBaseService;
    use \App\Services\TraitDeclaredService;

    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getDeliveryCreationRequest($warehouseIds, $merchantId)
    {
        try {
            $deliveryCreationRequest = null;
            $esgOrders = $this->getEsgAllocateOrders($warehouseIds, $merchantId);
            $batchRequest = $this->getDeliveryCreationRequestBatch($esgOrders, $merchantId);
            return $this->getDeliveryCreationBatchRequest($batchRequest, $merchantId);
        } catch (\Exception $e) {
            mail("brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", "[Vanguard] delivery order Exception", "Message: ". $e->getMessage() .", Line: ". $e->getLine() .", File: ". $e->getFile());
        }
    }

    public function getDeliveryCreationRequestByOrderNo($esgOrderNoList, $merchantId)
    {
        $deliveryCreationRequest = null;
        $esgOrders = $this->getEsgAllocateOrdersByOrderNo($esgOrderNoList);
        $batchRequest = $this->getDeliveryCreationRequestBatch($esgOrders, $merchantId);
        return $this->getDeliveryCreationBatchRequest($batchRequest, $merchantId);
    }

    public function getDeliveryCreationRequestBatch($esgOrders, $merchantId)
    {
        $esgAllocateOrder = null; 
        if(!$esgOrders->isEmpty()){
            $batchRequest = $this->getNewBatchId("CREATE_DELIVERY",$this->wmsPlatform, $merchantId);
            foreach ($esgOrders as $esgOrder) {
                $trackingNo = null;
                foreach ($esgOrder->soAllocate as $soAllocate) {
                    $warehouseId = $soAllocate->warehouse_id;
                }
                $this->deliveryOrderCreationRequest($batchRequest->id , $esgOrder, $warehouseId, $merchantId);
            }
            if(!empty($this->message)){
                $this->sendAlertEmail($this->message);
            }
            return $batchRequest;
        }
    }

    public function getDeliveryCreationBatchRequest($batchRequest, $merchantId)
    {
        if(!empty($batchRequest)){
            $requestLogs = IwmsDeliveryOrderLog::where("merchant_id", $merchantId)
                    ->where("batch_id",$batchRequest->id)    
                    ->pluck("request_log")
                    ->all();
            if(!empty($requestLogs)){
                foreach ($requestLogs as $requestLog) {
                    $deliveryCreationRequest[] = json_decode($requestLog);
                }
                $request = array(
                    "batchRequest" => $batchRequest,
                    "requestBody" => $deliveryCreationRequest
                );
                $batchRequest->request_log = json_encode($deliveryCreationRequest);
                $batchRequest->save();
                return $request;
            } else {
                $batchRequest->remark = "No delivery order request need send to wms";
                $batchRequest->status = "CE";
                $batchRequest->save();
            }
        }
    }

    private function deliveryOrderCreationRequest($batchId , $esgOrder, $warehouseId, $merchantId)
    {
        $deliveryCreationRequest = $this->getDeliveryCreationObject($esgOrder, $warehouseId, $merchantId);
        if ($deliveryCreationRequest) {
            $this->_saveIwmsDeliveryOrderRequestData($batchId,$deliveryCreationRequest);
        }
    }

    public function sendAlertEmail($message)
    {
        $subject = "Alert, Lack ESG with IWMS data mapping, It's blocked some order into the WMS, Please in time check it";
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        $alertEmail = "privatelabel-log@eservicesgroup.com";
        $msg = null;
        if (isset($this->message['warehouse'])) {
            $msg .= "Here ESG warehouse ID need with IWMS fisrt mapping\r\n";
            $warehouseNotes = array_unique($this->message['warehouse']);
            foreach ($warehouseNotes as $merchantId => $warehouseNote) {
                foreach ($warehouseNote as $key => $warehouseId) {
                    $msg .= "Merchant ID: $merchantId, Warehouse ID: $warehouseId\r\n";
                }
            }
        }
        $msg .= "\r\n";
        if (isset($this->message['courier'])) {
            $msg .= "Here ESG Courier ID need with IWMS fisrt mapping\r\n";
            $courierNotes = array_unique($this->message['courier']);
            foreach ($courierNotes as $merchantId => $courierNote) {
                foreach ($courierNote as $key => $courierId) {
                    $msg .= "Merchant ID: $merchantId, Courier ID: $courierId\r\n";
                }
            }
        }
        if (isset($this->message['so_no'])) {
            $msg .= "Has been blocked some orders: \r\n";
            $msg .= implode(", ", $this->message['so_no']) ."\r\n";
        }
        if($msg){
            mail("{$alertEmail}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
        }
    }

    private function getDeliveryCreationObject($esgOrder, $warehouseId, $merchantId)
    {
        $trackingNo = null;
        $courierId = $esgOrder->esg_quotation_courier_id;
        $iwmsWarehouseCode = $this->getIwmsWarehouseCode($warehouseId, $merchantId);
        $iwmsCourierCode = $this->getIwmsCourierCode($courierId, $merchantId, $this->wmsPlatform);
        if ($iwmsWarehouseCode === null || $iwmsCourierCode === null) {
            if ($iwmsWarehouseCode === null) {
                $this->_setWarehouseMessage($merchantId, $warehouseId);
            }
            if ($iwmsCourierCode === null) {
                $this->_setCourierMessage($merchantId, $courierId);
            }
            $this->_setSoNoMessage($esgOrder->so_no);
            return false;
        }
        $declaredObject = $this->getOrderDeclaredObject($esgOrder);
        //send remark for depx and fedx for 4px
        $extra_instruction = "";
        if(in_array($esgOrder->esg_quotation_courier_id, array("52","29"))){
            $extra_instruction = $esgOrder->courierInfo->courier_name;
        }

        $address = $esgOrder->delivery_address;
        if(in_array($iwmsCourierCode, $this->lgsCourier)){
            if(!empty($esgOrder->iwmsLgsOrderStatusLog)){
                $trackingNo = $esgOrder->iwmsLgsOrderStatusLog->tracking_no;
                $address = mb_substr($esgOrder->delivery_address, 0, 120, "utf-8");
                $address = (preg_replace( "/\r|\n/", "", $address));
            }else{
                return false;
            }
        }
        $deliveryOrderObj = array(
            "wms_platform" => $this->wmsPlatform,
            "iwms_warehouse_code" => $iwmsWarehouseCode,
            "reference_no" => $esgOrder->so_no,
            "iwms_courier_code" => $iwmsCourierCode,
            "marketplace_reference_no" => $esgOrder->platform_order_id,
            "marketplace_platform_id" => $esgOrder->biz_type."-ESG-".$esgOrder->delivery_country_id,
            "merchant_id" => $merchantId,
            "sub_merchant_id" => $esgOrder->sellingPlatform->merchant_id,
            "tracking_no" => $trackingNo,
            "store_name" => $esgOrder->sellingPlatform->store_name,
            "delivery_name" => $esgOrder->delivery_name,
            "company" => $esgOrder->delivery_company,
            "email" => $esgOrder->client->email,
            "country" => $esgOrder->delivery_country_id,
            "city" => $esgOrder->delivery_city,
            "state" => $esgOrder->delivery_state ? $esgOrder->delivery_state : "x",
            "address" => $address,
            "postal" => $esgOrder->delivery_postcode,
            "phone" => $this->getEsgOrderPhone($esgOrder),
            "declared_value" => $declaredObject["declared_value"],
            "declared_currency" => $declaredObject["declared_currency"],
            "amount_in_hkd" => '0',
            "amount_in_usd" => '0',
            "extra_instruction" => $extra_instruction,
            //"doorplate" => $esgOrder->doorplate,
        );
        if($this->validAwbCourierLabelUrl($esgOrder->esg_quotation_courier_id, $merchantId)){
            $deliveryOrderObj["shipping_label_url"] = $this->getEsgOrderAwbLabelUrl($esgOrder);
            $deliveryOrderObj["tracking_no"] = $this->getIwmsCourierOrderLogTrackingNo($esgOrder);
        }
        //
        if($this->validInvoiceLabelUrl($esgOrder->esg_quotation_courier_id, $merchantId)){
            $deliveryOrderObj["invoice_label_url"] = $this->getEsgOrderInvoiceLabelUrl($esgOrder);
        }
        foreach ($esgOrder->soItem as $esgOrderItem) {
            $hsCode = null; $hsDescription = null;
            if(isset($declaredObject["items"][$esgOrderItem->prod_sku]["code"])){
                $hsCode = $declaredObject["items"][$esgOrderItem->prod_sku]["code"];
            }
            if(isset($declaredObject["items"][$esgOrderItem->prod_sku]["prod_desc"])){
                $hsDescription = $declaredObject["items"][$esgOrderItem->prod_sku]["declared_desc"];
            }
            $deliveryOrderItem = array(
                "sku" => $esgOrderItem->prod_sku,
                "product_name" => (preg_replace( "/\r|\n/", "", $esgOrderItem->prod_name)),
                "quantity" => $esgOrderItem->qty,
                "hscode" => $hsCode,
                "hsdescription" => $hsDescription,
                "item_declared_value" => $declaredObject["items"][$esgOrderItem->prod_sku]["item_declared_value"],
                "unit_price_hkd" => '0',
                "unit_price_usd" => '0',
                "marketplace_items_serial" => $esgOrderItem->ext_item_cd,
                //"skuLabelCode" => '',
            );
            $deliveryOrderObj["item"][] = $deliveryOrderItem;
            if(in_array($esgOrderItem->product->battery, [1, 2])){
                $isBattery = 1;
            }
        }
        if(isset($isBattery) && $isBattery){
            $deliveryOrderObj["battery"] = 1;
            //$deliveryOrderObj["msds_label_url"] = $this->getEsgOrderMsdsLabelUrl();
        }
        return $deliveryOrderObj;
    }

    public function _saveIwmsDeliveryOrderRequestData($batchId, $requestData)
    {
        $iwmsDeliveryOrderLog = new IwmsDeliveryOrderLog();
        $iwmsDeliveryOrderLog->batch_id = $batchId;
        $iwmsDeliveryOrderLog->wms_platform = $requestData["wms_platform"];
        $iwmsDeliveryOrderLog->merchant_id = $requestData["merchant_id"];
        $iwmsDeliveryOrderLog->sub_merchant_id = $requestData["sub_merchant_id"];
        $iwmsDeliveryOrderLog->tracking_no = $requestData["tracking_no"];
        $iwmsDeliveryOrderLog->store_name = $requestData["store_name"];
        $iwmsDeliveryOrderLog->reference_no = $requestData["reference_no"];
        $iwmsDeliveryOrderLog->iwms_warehouse_code = $requestData["iwms_warehouse_code"];
        $iwmsDeliveryOrderLog->marketplace_platform_id = $requestData["marketplace_platform_id"];
        $iwmsDeliveryOrderLog->iwms_courier_code = $requestData["iwms_courier_code"];
        $iwmsDeliveryOrderLog->platform_order_id = $requestData["marketplace_reference_no"];
        $iwmsDeliveryOrderLog->request_log = json_encode($requestData);
        $iwmsDeliveryOrderLog->status = "0";
        $iwmsDeliveryOrderLog->repeat_request = "0";
        $iwmsDeliveryOrderLog->save();
        return $iwmsDeliveryOrderLog;
    }

    public function getEsgAllocateOrders($warehouseToIwms, $merchantId)
    {
        if($merchantId == "ESG"){
            $esgOrders = $this->getEsgChinaAllocateOrders($warehouseToIwms);
        }else if ($merchantId == "ESG-HK-TEST"){
            $esgOrders = $this->getEsgAccelerateAllocateOrders($warehouseToIwms);
        }
        return $this->checkEsgAllocateOrders($esgOrders, $merchantId);
    }

    public function getEsgChinaAllocateOrders($warehouseToIwms)
    {
        //$this->fromData = date("Y-m-d 00:00:00");
        $this->fromData = date("2017-01-21 00:00:00");
        $this->toDate = date("Y-m-d 23:59:59");
        $this->warehouseIds = $warehouseToIwms;
        return So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereNotNull("esg_quotation_courier_id")
            ->where("dnote_invoice_status", 2)
            ->whereHas('sellingPlatform', function ($query) {
                $query->whereNotIn('merchant_id', $this->excludeMerchant);
            })
            ->whereHas('soAllocate', function ($query) {
                $query->whereIn('warehouse_id', $this->warehouseIds)
                    ->where("status", 1)
                    ->where("modify_on", ">=", $this->fromData)
                    ->where("modify_on", "<=", $this->toDate);
            })
            ->with("client")
            ->with("soItem")
            ->limit(100)
            ->get();
    }

    public function getEsgAccelerateAllocateOrders($warehouseToIwms)
    {
        //$this->fromData = date("Y-m-d 00:00:00");
        $this->fromData = date("2017-03-16 00:00:00");
        $this->toDate = date("Y-m-d 23:59:59");
        $this->warehouseIds = $warehouseToIwms;
        return So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereNotNull("esg_quotation_courier_id")
            ->where("dnote_invoice_status", 2)
            ->whereHas('sellingPlatform', function ($query) {
                $query->where('merchant_id', "ESG");
            })
            ->whereHas('soAllocate', function ($query) {
                $query->whereIn('warehouse_id', $this->warehouseIds)
                    ->where("status", 1)
                    ->where("modify_on", ">=", $this->fromData)
                    ->where("modify_on", "<=", $this->toDate);
            })
            ->with("client")
            ->with("soItem")
            ->limit(10)
            ->get();
    }

    private function getEsgAllocateOrdersByOrderNo($esgOrderNoList)
    {
        $esgOrders = So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereIn("so_no", $esgOrderNoList)
            ->with("sellingPlatform")
            ->with("soAllocate")
            ->with("client")
            ->with("soItem")
            ->get();
        return $esgOrders;
    }

    private function checkEsgAllocateOrders($esgOrders, $merchantId)
    {
        $validEsgOrders = new Collection();
        if(!$esgOrders->isEmpty()){
            foreach($esgOrders as $esgOrder) {
                $valid = null;
                $postCode = $this->getValidPostCode($esgOrder->delivery_postcode, $esgOrder->delivery_country_id);
                if(!empty($postCode)){
                    $esgOrder->delivery_postcode = $postCode;
                }else{
                    $errorPostCodes[] =  $esgOrder->so_no;
                    continue;
                }
                /*$validAwbLable = $this->validEsgOrderAwbLableStatus($esgOrder, $merchantId);
                if(!$validAwbLable){
                    continue;
                }*/
                $repeatResult = $this->validRepeatRequestDeliveryOrder($esgOrder, $merchantId);
                if($repeatResult){
                    $validEsgOrders[] = $esgOrder;
                }
            }
            if(isset($errorPostCodeOrders) && $errorPostCodeOrders){
                $msg = null;
                $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
                $subject = "OMS create order failed.";
                foreach ($errorPostCodeOrders as $errorOrderNo) {
                    $msg .= "Order ID".$errorOrderNo." postal is null";
                }
                mail("privatelabel-log@eservicesgroup.com,  jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
            }
        }
        return $validEsgOrders;
    }

    private function validEsgOrderAwbLableStatus($esgOrder, $merchantId)
    {
        $awbCourierList = $this->getPostAwbLabelToIwmsCourierList($merchantId);
        if(in_array($esgOrder->esg_quotation_courier_id, $awbCourierList)){
            if($esgOrder->waybill_status == 2){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }

    private function validRepeatRequestDeliveryOrder($esgOrder, $merchantId)
    {
        $this->esgOrderNo = $esgOrder->so_no;
        $this->merchantId = $merchantId;
        $requestOrderLog = IwmsDeliveryOrderLog::where("merchant_id", $merchantId)
                        ->where("reference_no",$esgOrder->so_no)
                        ->where("status", 1)
                        ->orWhere(function ($query) {
                            $query->where("merchant_id", $this->merchantId)
                                ->where("reference_no", $this->esgOrderNo)
                                ->whereIn("status", array("0","-1"))
                                ->where("repeat_request", "!=", 1);
                            })
                        ->first();
        if(!empty($requestOrderLog)){
            return false;
        }else{
            return true;
        }
    }

    public function getDeliveryOrderReportRequest($warehouseIds, $merchantId)
    {
        $deliveryCreationRequest = null;
        $batchRequest = $this->getDeliveryCreationRequestBatch($warehouseIds, $merchantId);
        if(!empty($batchRequest)){
            $requestLogs = IwmsDeliveryOrderLog::where("merchant_id", $merchantId)
                ->where("batch_id",$batchRequest->id)
                ->pluck("request_log")
                ->all();
            if(!empty($requestLogs)){
                foreach ($requestLogs as $requestLog) {
                    $deliveryCreationRequest[] = json_decode($requestLog);
                }
                $request = array(
                    "batchRequest" => $batchRequest,
                    "requestBody" => $deliveryCreationRequest
                );
                return $request;
            }
        }
    }

    private function getEsgOrderPhone($esgOrder)
    {
        $phone = "0-0-0";
        $soPhone =  $esgOrder->del_tel_1.$esgOrder->del_tel_2.$esgOrder->del_tel_3;
        $clientPhone = $esgOrder->client->tel_1.$esgOrder->client->tel_2.$esgOrder->client->tel_3;
        if ($soPhone ) {
            $phone = $soPhone;
        } else if ($clientPhone) {
            $phone = $clientPhone;
        }
        return $phone;
    }

    private function _setWarehouseMessage($merchantId, $warehouseId)
    {
        if (! isset($this->message['warehouse'])) {
            $this->message['warehouse'] = [];
        }
        if (isset($this->message['warehouse'])
            && ! isset($this->message['warehouse'][$merchantId])
        ) {
            $this->message['warehouse'][$merchantId] = [];
        }
        $this->message['warehouse'][$merchantId][] = $warehouseId;
    }

    private function _setCourierMessage($merchantId, $courierId)
    {
        if (! isset($this->message['courier'])) {
            $this->message['courier'] = [];
        }
        if (isset($this->message['courier'])
            && ! isset($this->message['courier'][$merchantId])
        ) {
            $this->message['courier'][$merchantId] = [];
        }
        $this->message['courier'][$merchantId][$courierId] = $courierId;
    }

    private function _setSoNoMessage($so_no)
    {
        if (! isset($this->message['so_no'])) {
            $this->message['so_no'] = [];
        }
        $this->message['so_no'][] = $so_no;
    }

    private function getValidPostCode($postCode, $deliveryCountry)
    {
        if(empty($postCode)){
            if($deliveryCountry == "HK"){
                return "00000";
            }else{
                return null;
            }
        }else{
            if($deliveryCountry == "US" && strlen($postCode) < 5){
                return str_pad($esgOrder->delivery_postcode, 5, "0", STR_PAD_LEFT);
            }else{
                return $postCode;
            }
        }
    }

    private function getIwmsCourierOrderLogTrackingNo($esgOrder)
    {
        $iwmsCourierOrderLog = IwmsCourierOrderLog::where("reference_no", $esgOrder->so_no)
                            ->where("status", "1")
                            ->first();
        if(!empty($iwmsCourierOrderLog)){
            return $iwmsCourierOrderLog->wms_order_code;
        }
    }

    private function validAwbCourierLabelUrl($merchantCourierId, $merchantId)
    {
        $awbLabelToIwmsCourierList = $this->getPostAwbLabelToIwmsCourierList($merchantId);
        if(in_array($merchantCourierId, $awbLabelToIwmsCourierList)){
            return true;
        }
    }

    private function validInvoiceLabelUrl($merchantCourierId, $merchantId)
    {
        $invoiceLabelToIwmsCourierList = $this->getPostInvoiceLabelToIwmsCourierList($merchantId);
        if(in_array($merchantCourierId, $invoiceLabelToIwmsCourierList)){
            return true;
        }
    }

}