<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use App\Models\WmsWarehouseMapping;
use App\Models\InvMovement;
use App\Models\PlatformMarketInventory;
/**
*
*/ 
trait ApiBaseOrderTraitService
{
    use ApiPlatformTraitService;
    private $schedule;

    public function checkWarehouseInventory($platformMarketOrder,$orginWarehouse)
    {
        $updateAction = true; $warehouseInventory = null; $updateObject = null;
        $newWarehouse = $orginWarehouse;
        foreach($platformMarketOrder->platformMarketOrderItem as $orderItem){
            $remainInventroy = $newWarehouse[$orderItem->seller_sku]["inventory"] - $orderItem->quantity_ordered;
            if($remainInventroy >= 0){
                $newWarehouse[$orderItem->seller_sku]["inventory"] = $remainInventroy;
                if(isset($updateObject[$orderItem->seller_sku])){
                    $updateObject[$orderItem->seller_sku]["qty"] += $orderItem->quantity_ordered;
                }else{
                    $updateObject[$orderItem->seller_sku]["qty"] = $orderItem->quantity_ordered;
                    $updateObject[$orderItem->seller_sku]["sku"] = $newWarehouse[$orderItem->seller_sku]["sku"];
                    $updateObject[$orderItem->seller_sku]["warehouse_id"] = $newWarehouse[$orderItem->seller_sku]["warehouse_id"];
                }
            }else{
                $updateAction = false;
            }
        }
        $warehouseInventory["warehouse"] = $updateAction ? $newWarehouse : $orginWarehouse;
        $warehouseInventory["updateObject"] = $updateObject;
        return $warehouseInventory;
    }

    public function updateWarehouseInventory($soNo,$updateObjects)
    {
        foreach($updateObjects as $updateObject){
            $object = array(
                "ship_ref" => $soNo."-01",
                "sku" => $updateObject["sku"],
                "qty" => $updateObject["qty"],
                "type" => "C",
                "from_location" => $updateObject["warehouse_id"],
                "reason" => "LAZADA READY TO SHIP",
                "status" => "OT"
            );
            $invMovement = InvMovement::updateOrCreate(
                [
                    'ship_ref' => $soNo."-01",
                    'sku' => $updateObject["sku"],
                ],
                $object
            );
        }
    }

    public function updatePlatformMarketInventory($order,$updateObject)
    {
        $countryCode = strtoupper(substr($order->platform, -2));
        $marketplaceId = strtoupper(substr($order->platform, 0, -2));
        $marketplaceSkuMapping = MarketplaceSkuMapping::where("marketplace_sku","=",$updateObject["sku"])
                ->where("marketplace_id","=",$marketplaceId)
                ->where("country_id","=",$countryCode)
                ->with('merchantProductMapping')
                ->first();
        $platformMarketInventory = PlatformMarketInventory::where("store_id",$order->store_id)
                ->where("warehouse_id",$updateObject["warehouse_id"])
                ->where("mattel_sku",$marketplaceSkuMapping->merchantProductMapping->merchant_sku)
                ->first();
        if($platformMarketInventory){
            $remainInventroy = $platformMarketInventory->inventory - $updateObject["qty"];
            $platformMarketInventory->inventory = $remainInventroy;
            $platformMarketInventory->save();
        }
    }

    public function getSchedule()
    {
        return $this->schedule;
    }

    public function setSchedule($value)
    {
        $this->schedule = $value;
    }
}
