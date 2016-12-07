<?php

namespace App\Services\IwmsApi;

use App\Models\So;

trait IwmsBaseService
{
    public function gerEsgAllocateOrderRequest($warehouseId)
    {
        $esgAllocateOrder = null;
        $esgOrders = So::where("status",5)
            ->with("client")
            ->with("soItem")
            ->with("soAllocate")
            ->get();
        foreach ($esgOrders as $esgOrder) {
            if(!$esgOrder->soAllocate->isEmpty()){
                foreach ($esgOrder->soAllocate as $soAllocate) {
                    if($soAllocate->warehouse_id == $warehouseId){
                        if($soAllocate->soShipment){
                            $esgAllocateOrder[] = $this->getDeliveryCreationRequest($esgOrder,$soAllocate);
                        }
                    }
                }
            }  
        }
        return $esgAllocateOrder;
    }

    private function getDeliveryCreationRequest($esgOrder,$soAllocate)
    {
        $deliveryOrderObj = array(
            "warehouse" => $soAllocate->warehouse_id,
            "reference_no" => $esgOrder->so_no,
            "courier_id" => $soAllocate->soShipment->courier_id,
            "marketplace_reference_no" => $esgOrder->platform_order_id,
            "sales_platform" => $esgOrder->biz_type,
            //"description" => '',
            "delivery_name" => $esgOrder->delivery_name,
            "company" => $esgOrder->delivery_company,
            "email" => $esgOrder->client->email,
            "country" => $esgOrder->delivery_country_id,
            "city" => $esgOrder->delivery_city,
            "state" => $esgOrder->delivery_state,
            "address" => $esgOrder->delivery_address,
            "postal" => $esgOrder->delivery_postcode,
            "phone" => $esgOrder->del_tel_1.$esgOrder->del_tel_2.$esgOrder->del_tel_3,
            //"doorplate" => $esgOrder->doorplate,
        );
        foreach ($esgOrder->soItem as $esgOrderItem) {
            $deliveryOrderItem = array(
                "sku" => $esgOrderItem->prod_sku,
                "quantity" => $esgOrderItem->qty,
                //"skuLabelCode" => '',
            );
            $deliveryOrderObj["deliveryOrderItem"][] = $deliveryOrderItem;
        }
    }   

}