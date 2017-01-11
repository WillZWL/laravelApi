<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\IwmsDeliveryOrderLog;
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

    use \App\Services\IwmsApi\IwmsBaseService;

    public function __construct($wmsPlatform)
    {
        $this->wmsPlatform = $wmsPlatform;
    }

    public function getDeliveryCreationRequest($warehouseIds)
    {
        $deliveryCreationRequest = null;
        $batchRequest = $this->getDeliveryCreationRequestBatch($warehouseIds);
        if(!empty($batchRequest)){
            $requestLogs = IwmsDeliveryOrderLog::where("batch_id",$batchRequest->id)->pluck("request_log")->all();
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

    public function getDeliveryCreationRequestBatch($warehouseIds)
    {
        $esgAllocateOrder = null; $merchantId = "ESG"; 
        $courier =  array("4PX-PL-LGS"); 
        $esgOrders = $this->getEsgAllocateOrders($warehouseIds);
        if(!$esgOrders->isEmpty()){
            $batchRequest = $this->getNewBatchId("CREATE_DELIVERY",$this->wmsPlatform,$merchantId);
            foreach ($esgOrders as $esgOrder) {
                foreach ($esgOrder->soAllocate as $soAllocate) {
                    $warehouseId = $soAllocate->warehouse_id;
                }
                $iwmsCourierCode = $this->getIwmsCourierCode($esgOrder->esg_quotation_courier_id,$merchantId);
                if(in_array( $iwmsCourierCode, $courier)){
                    $IwmsLgsOrderStatusLog = $this->setIwmsLgsOrderStatusToShip($esgOrder, $merchantId);
                    if(!empty($IwmsLgsOrderStatusLog)) {
                        continue;
                    }
                }
                $deliveryCreationRequest = $this->getDeliveryCreationObject($esgOrder,$esgOrder->esg_quotation_courier_id,$warehouseId);
                if ($deliveryCreationRequest) {
                    $this->_saveIwmsDeliveryOrderRequestData($batchRequest->id,$deliveryCreationRequest);
                }
            }
            if (null !== $this->message) {
                $this->sendAlertEmail($this->message);
            }
            return $batchRequest;
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

    private function getDeliveryCreationObject($esgOrder,$courierId,$warehouseId)
    {
        $merchantId = "ESG";
        $iwmsWarehouseCode = $this->getIwmsWarehouseCode($warehouseId,$merchantId);
        $iwmsCourierCode = $this->getIwmsCourierCode($courierId,$merchantId);

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
        $extra_instruction = "";
        if(in_array($esgOrder->esg_quotation_courier_id, array("52","29"))){
            $extra_instruction = $esgOrder->courierInfo->courier_name;
        }
        $deliveryOrderObj = array(
            "wms_platform" => $this->wmsPlatform,
            "iwms_warehouse_code" => $iwmsWarehouseCode,
            "reference_no" => $esgOrder->so_no,
            "iwms_courier_code" => $iwmsCourierCode,
            "marketplace_reference_no" => $esgOrder->platform_order_id,
            "marketplace_platform_id" => $esgOrder->biz_type."-ESG-".$esgOrder->delivery_country_id,
            "merchant_id" => $merchantId,
            "delivery_name" => $esgOrder->delivery_name,
            "company" => $esgOrder->delivery_company,
            "email" => $esgOrder->client->email,
            "country" => $esgOrder->delivery_country_id,
            "city" => $esgOrder->delivery_city,
            "state" => $esgOrder->delivery_state ? $esgOrder->delivery_state : "x",
            "address" => $esgOrder->delivery_address,
            "postal" => $esgOrder->delivery_postcode,
            "phone" => $this->getEsgOrderPhone($esgOrder),
            "amount_in_hkd" => '0',
            "amount_in_usd" => '0',
            "extra_instruction" => $extra_instruction,
            //"doorplate" => $esgOrder->doorplate,
        );
        foreach ($esgOrder->soItem as $esgOrderItem) {
            $hscode = null; $hsDescription = null;
            if($esgOrderItem->hscodeCategory){
                $hscode = $esgOrderItem->hscodeCategory->general_hscode;
                $hsDescription = $esgOrderItem->hscodeCategory->description;
            }
            $deliveryOrderItem = array(
                "sku" => $esgOrderItem->prod_sku,
                "product_name" => $esgOrderItem->prod_name,
                "quantity" => $esgOrderItem->qty,
                "hscode" => $hscode,
                "hsDescription" => $hsDescription,
                "unit_price_hkd" => '0',
                "unit_price_usd" => '0',
                "marketplace_items_serial" => $esgOrderItem->ext_item_cd,
                //"skuLabelCode" => '',
            );
            $deliveryOrderObj["item"][] = $deliveryOrderItem;
        }
        return $deliveryOrderObj;
    }

    public function _saveIwmsDeliveryOrderRequestData($batchId,$requestData)
    {
        $iwmsDeliveryOrderLog = new IwmsDeliveryOrderLog();
        $iwmsDeliveryOrderLog->batch_id = $batchId;
        $iwmsDeliveryOrderLog->wms_platform = $requestData["wms_platform"];
        $iwmsDeliveryOrderLog->merchant_id = $requestData["merchant_id"];
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

    public function getEsgAllocateOrders($warehouseToIwms)
    {
        $this->fromData = date("Y-m-d 00:00:00");
        $this->toDate = date("Y-m-d 23:59:59");
        $this->warehouseIds = $warehouseToIwms;
        $esgOrders = So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereNotNull("esg_quotation_courier_id")
            ->whereHas('soAllocate', function ($query) {
                $query->whereIn('warehouse_id', $this->warehouseIds)
                    ->where("status", 1)
                    ->where("modify_on", ">=", $this->fromData)
                    ->where("modify_on", "<=", $this->toDate);
            })
            ->with("client")
            ->with("soItem")
            ->get();
        return $this->checkEsgAllocateOrders($esgOrders);
    }

    private function checkEsgAllocateOrders($esgOrders)
    {
        $validEsgOrders = new Collection();
        if(!$esgOrders->isEmpty()){
            foreach($esgOrders as $esgOrder) {
                $valid = null;
                $requestOrderLog = IwmsDeliveryOrderLog::where("merchant_id", "ESG")->where("reference_no",$esgOrder->so_no)
                        ->where("status", 1)
                        ->first();
                if(empty($requestOrderLog)){
                    $repeatRequestOrderLog = IwmsDeliveryOrderLog::where("merchant_id", "ESG")->where("reference_no",$esgOrder->so_no)
                        ->where("status", 0)
                        ->where("repeat_request", "!=", 1)
                        ->first();
                    if(empty($repeatRequestOrderLog)){
                        $validEsgOrders[] = $esgOrder;
                    }
                }
            }
        }
        return $validEsgOrders;
    }

    public function getDeliveryOrderReportRequest($warehouseIds)
    {
        $deliveryCreationRequest = null;
        $batchRequest = $this->getDeliveryCreationRequestBatch($warehouseIds);
        if(!empty($batchRequest)){
            $requestLogs = IwmsDeliveryOrderLog::where("batch_id",$batchRequest->id)->pluck("request_log")->all();
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

    public function setIwmsLgsOrderStatusToShip($esgOrder, $merchantId)
    {
        $result = null;
        $iwmsLgsOrderStatusLog = IwmsLgsOrderStatusLog::where("platform_order_no",$esgOrder->platform_order_id)
                        ->first();
        if((!empty($iwmsLgsOrderStatusLog) && $iwmsLgsOrderStatusLog->status != 1) 
            ||empty($iwmsLgsOrderStatusLog)) {
            $result = $this->getApiLazadaService()->IwmsSetLgsOrderReadyToShip($esgOrder);
        }
        if(isset($result["tracking_no"]) && $result["tracking_no"]){
            $object['iwms_platform'] = $this->wmsPlatform;
            $object['esg_courier_id'] = $esgOrder->esg_quotation_courier_id;
            $object['so_no'] = $esgOrder->so_no;
            $object['platform_order_no'] = $esgOrder->platform_order_id;
            $object['tracking_no'] = $result["tracking_no"];
            if(isset($result["valid"]) && $result["valid"]){
                $object['status'] = 1;   
            }
            return IwmsLgsOrderStatusLog::updateOrCreate(['so_no' => $object['so_no']],$object); 
        } 
    }

    public function getApiLazadaService()
    {
        if ($this->apiLazadaService == null) {
            $this->apiLazadaService = App::make("App\Services\ApiLazadaService");
        }
        return $this->apiLazadaService;
    }

}