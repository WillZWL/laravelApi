<?php

namespace App\Services\IwmsApi;

use App\Models\So;

trait IwmsBaseService
{
    private $warehouseId = null;
    public function gerEsgAllocateOrderRequest($warehouseId)
    {
        $esgAllocateOrder = null;
        $this->warehouseId = $warehouseId;
        $esgOrders = So::where("status",5)
            ->whereHas('soAllocate', function ($query) {
                $query->where('warehouse_id', '=', $this->warehouseId);
            })
            ->with("client")
            ->with("soItem")
            ->get();
        if(!$esgOrders->isEmpty()){
            foreach ($esgOrders as $esgOrder) {
                foreach ($esgOrder->soAllocate as $soAllocate) {
                    if($soAllocate->soShipment){
                        $courierId = $soAllocate->soShipment->courier_id
                    }else{
                        continue;
                    }
                }
                $esgAllocateOrder[] = $this->getDeliveryCreationRequest($esgOrder,$courierId,$warehouseId);
            }  
        }
        return $esgAllocateOrder;
    }

    private function getDeliveryCreationRequest($esgOrder,$courierId,$warehouseId)
    {
        $deliveryOrderObj = array(
            "warehouse" => $warehouseId,
            "reference_no" => $esgOrder->so_no,
            "courier_id" => $courierId,
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
        return $deliveryOrderObj;
    }   

}