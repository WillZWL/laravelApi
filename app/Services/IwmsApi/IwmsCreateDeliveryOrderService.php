<?php

namespace App\Services\IwmsApi;

use App\Models\So;
use App\Models\IwmsDeliveryOrderLog;

trait IwmsCreateDeliveryOrderService
{
    private $warehouseId = null;
    
    use IwmsBaseService;

    public function getDeliveryCreationRequest($warehouseId)
    {
        $deliveryCreationRequest = null;
        $batchRequest = $this->getDeliveryCreationRequestBatch($warehouseId);
        $requestLogs = IwmsDeliveryOrderLog::where("batch_id",$batchRequest->id)->pluck("request_log")->all();
        if(!empty($requestLogs)){
            foreach ($requestLogs as $requestLog) {
                $deliveryCreationRequest[] = json_decode($requestLog);
            }
            $request = array(
                "batchId" => $batchRequest->id,
                "requestBody" => $deliveryCreationRequest
            );
            return $request;
        }
    }

    public function getDeliveryCreationRequestBatch($warehouseId)
    {
        $esgAllocateOrder = null;
        $esgOrders = $this->getEsgAllocateOrders($warehouseId);
        if(!$esgOrders->isEmpty()){
            $batchRequest = $this->getBatchId("CREATE_DELIVERY");
            foreach ($esgOrders as $esgOrder) {
                $courierId = null;
                foreach ($esgOrder->soAllocate as $soAllocate) {
                    if($soAllocate->soShipment && $soAllocate->soShipment->status == "1"){
                        $courierId = $soAllocate->soShipment->courier_id;
                    }else{
                        continue;
                    }
                }
                if(empty($courierId)){
                    continue;
                }
                $deliveryCreationRequest = $this->getDeliveryCreationObject($esgOrder,$courierId,$warehouseId);
                $this->_saveIwmsDeliveryOrderRequestData($batchRequest->id,$deliveryCreationRequest);
            } 
        }
        return $batchRequest;
    }

    private function getDeliveryCreationObject($esgOrder,$courierId,$warehouseId)
    {
        $merchantId = "ESG";
        $deliveryOrderObj = array(
            "wms_platform" => $this->wmsPlatform,
            "iwms_warehouse_code" => $this->getIwmsWarehouseCode($warehouseId,$merchantId),
            "reference_no" => $esgOrder->so_no,
            "iwms_courier_code" => $this->getIwmsCourierCode($courierId,$merchantId),
            "marketplace_reference_no" => $esgOrder->platform_order_id,
            "marketplace_platform_id" => $esgOrder->biz_type."-ESG-".$esgOrder->delivery_country_id,
            "merchant_id" => $merchantId,
            "delivery_name" => $esgOrder->delivery_name,
            "company" => $esgOrder->delivery_company,
            "email" => $esgOrder->client->email,
            "country" => $esgOrder->delivery_country_id,
            "city" => $esgOrder->delivery_city,
            "state" => $esgOrder->delivery_state,
            "address" => $esgOrder->delivery_address,
            "postal" => $esgOrder->delivery_postcode,
            "phone" => $esgOrder->client->del_tel_1.$esgOrder->client->del_tel_2.$esgOrder->client->del_tel_3,
            "amount_in_hkd" => $esgOrder->amount * $esgOrder->rate_to_hkd,
            "amount_in_usd" => '0',
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
                "unit_price_hkd" => $esgOrderItem->unit_price * $esgOrder->rate_to_hkd,
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
        $validIwmsDeliveryOrderLog = IwmsDeliveryOrderLog::where("reference_no",$requestData['reference_no'])
            ->where("repeat_request",0)
            ->get();
        if($validIwmsDeliveryOrderLog->isEmpty()){
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
    } 

    public function _saveIwmsDeliveryOrderResponseData($batchId,$responseJsons)
    {
        $responseDatas = json_decode($responseJsons);
        foreach ($responseData as $key => $value) {
            $object = array(
                "response_message"=> $value["message"],
                "status"=> $this->iwmStatus[$value["status"]],
                "response_log"=> json_encode($value),
            );
            IwmsDeliveryOrderLog::where('batch_id',$batchId)
                    ->where("reference_no",$value["reference_no"])
                    ->update($object);
        }
    }

    public function getEsgAllocateOrders($warehouseId)
    {
        $this->warehouseId = $warehouseId;
        return $esgOrders = So::where("status",5)
            ->where("refund_status","0")
            ->where("hold_status","0")
            ->where("prepay_hold_status","0")
            ->whereHas('soAllocate', function ($query) {
                $query->where('warehouse_id', '=', $this->warehouseId);
            })
            ->with("client")
            ->with("soItem")
            ->get();
    }

}