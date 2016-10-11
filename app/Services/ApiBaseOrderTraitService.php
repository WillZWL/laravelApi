<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use App\Models\WmsWarehouseMapping;
use App\Models\InvMovement;
/**
*
*/ 
trait ApiBaseOrderTraitService
{
    use ApiPlatformTraitService;
    private $schedule;

    public function getWmsWarehouseSkuOrderedList($warehouseOrderGroups)
    {
        $warehouseSkuOrderedList = array();
        foreach($warehouseOrderGroups as $warehouseId => $warehouseOrderGroup){
            $warehouseMapping = WmsWarehouseMapping::where("warehouse_id","=",$warehouseId)->first();
            $platformPrefix = strtoupper(substr($warehouseMapping->platform_id, 3, 2));
            $platformAcronym = strtoupper(substr($warehouseMapping->platform_id, 5, 2));
            $countryCode = strtoupper(substr($warehouseMapping->platform_id, -2));
            $marketplaceId = $platformPrefix.$this->platformAcronym[$platformAcronym];
            $marketplaceSkuList = MarketplaceSkuMapping::join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
                        ->join('brand', 'brand.id', '=', 'product.brand_id')
                        ->join('sku_mapping', 'sku_mapping.sku', '=', 'product.sku')
                        ->where("marketplace_id","=",$marketplaceId)
                        ->where("country_id","=",$countryCode)
                        ->select('marketplace_sku_mapping.sku','marketplace_sku_mapping.marketplace_sku','product.name as product_name','brand.brand_name','sku_mapping.ext_sku as master_sku')
                        ->get()
                        ->toArray();
            $platformSkuOrderedList = $this->getPlatformSkuOrderedList($warehouseOrderGroup,$marketplaceSkuList);
            $warehouseSkuOrderedList[$warehouseId] = $platformSkuOrderedList;
        }
        return $warehouseSkuOrderedList;
    }

    private function getPlatformSkuOrderedList($warehouseOrderGroup,$marketplaceSkuList)
    {
        $skuOrderedQtyList = null;$platformSkuOrderedList = null;
        foreach($warehouseOrderGroup as $warehouseOrder){
            if(isset($skuOrderedQtyList[$warehouseOrder->prod_sku])){
                $skuOrderedQtyList[$warehouseOrder->prod_sku] +=$warehouseOrder->qty;
            }else{
                $skuOrderedQtyList[$warehouseOrder->prod_sku] =$warehouseOrder->qty;
            }
        }
        foreach($marketplaceSkuList as $marketplaceSku){
            if(isset($skuOrderedQtyList[$marketplaceSku["sku"]])){
                $marketplaceSku["qty"] = $skuOrderedQtyList[$marketplaceSku["sku"]];
            }
            //can set only order show the product mapping info
            $platformSkuOrderedList[$marketplaceSku["marketplace_sku"]] = $marketplaceSku;
        }
        return $platformSkuOrderedList;
    }

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

    public function getSchedule()
    {
        return $this->schedule;
    }

    public function setSchedule($value)
    {
        $this->schedule = $value;
    }
}
