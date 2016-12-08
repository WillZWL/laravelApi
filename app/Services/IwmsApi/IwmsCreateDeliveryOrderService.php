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
        $batchRequest = $this->getDeliveryCreationRequestBatch($warehouseId);
        $deliveryCreationRequest = IwmsDeliveryOrderLog::where("batch_id",$batchRequest->id)->pluck("request_log")->get();
        $request = array(
            "batchId" => $batchRequest->id, 
            "requestBody" => $deliveryCreationRequest
        );
        return $request;
    }

    public function getDeliveryCreationRequestBatch($warehouseId)
    {
        $esgAllocateOrder = null;
        $esgOrders = $this->getEsgAllocateOrders($warehouseId);
        if(!$esgOrders->isEmpty()){
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
                $deliveryCreationRequest[] = $this->getDeliveryCreationObject($esgOrder,$courierId,$warehouseId);
                $batchRequest = $this->getBatchId("CREATE_DELIVERY",json_encode($deliveryCreationRequest));
                $this->_saveIwmsDeliveryOrderRequestData($batchRequest->id,$deliveryCreationRequest);
            } 
        }
        return $batchRequest;
    }

    private function getDeliveryCreationObject($esgOrder,$courierId,$warehouseId)
    {
        $deliveryOrderObj = array(
            "warehouse_id" => $warehouseId,
            "reference_no" => $esgOrder->so_no,
            "courier_id" => $courierId,
            "marketplace_reference_no" => $esgOrder->platform_order_id,
            "platform_id" => $esgOrder->biz_type."-ESG-".$esgOrder->delivery_country_id,
            "merchant_id" => "ESG",
            "delivery_name" => $esgOrder->delivery_name,
            "company" => $esgOrder->delivery_company,
            "email" => $esgOrder->client->email,
            "country" => $esgOrder->delivery_country_id,
            "city" => $esgOrder->delivery_city,
            "state" => $esgOrder->delivery_state,
            "address" => $esgOrder->delivery_address,
            "postal" => $esgOrder->delivery_postcode,
            "phone" => $esgOrder->del_tel_1.$esgOrder->del_tel_2.$esgOrder->del_tel_3,
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
                "unit_price_usd" => '0'
                //"skuLabelCode" => '',
            );
            $deliveryOrderObj["item"][] = $deliveryOrderItem;
        }
        return $deliveryOrderObj;
    }

    public function _saveIwmsDeliveryOrderRequestData($batchId,$requestData)
    {
        $object = array();
        $object["batch_id"] = $batchId;
        $object["reference_no"] = $requestData["reference_no"];
        $object["warehouse_id"] = $requestData["warehouse_id"];
        $object["platform_id"] = $requestData["platform_id"];
        $object["merchant_id"] = $requestData["merchant_id"];
        $object["courier_id"] = $requestData["courier_id"];
        $object["platform_order_no"] = $requestData["marketplace_reference_no"];
        $object["request_log"] = json_encode($requestData);
        $object["status"] = "N";
        $iwmsDeliveryOrderLog = IwmsDeliveryOrderLog::updateOrCreate(
            [
                'batch_id' => $batchId,
                'reference_no' => $object['reference_no'],
            ],$object
        );
        return $platformMarketShippingAddress->id;
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
            ->whereHas('soAllocate', function ($query) {
                $query->where('warehouse_id', '=', $this->warehouseId);
            })
            ->with("client")
            ->with("soItem")
            ->get();
    }

}