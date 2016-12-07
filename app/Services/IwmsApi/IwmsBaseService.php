<?php

namespace App\Services\IwmsApi;

use App\Models\So;

trait IwmsBaseService
{

    public function gerEsgAllocateOrder($warehouseId)
    {
        $esgOrders = So::where("status",5)
            ->with("soAllocate")
            ->get();
        foreach ($esgOrders as $esgOrder) {
            if(!$esgOrder->soAllocate->isEmpty()){
                foreach ($esgOrder->soAllocate as $soAllocate) {
                    if($soAllocate->warehouse_id == $warehouseId){
                        print_r($soAllocate->$soShipment);exit();
                    }
                }
            }  
        }
    }

}