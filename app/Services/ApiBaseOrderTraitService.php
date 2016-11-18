<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use App\Models\WmsWarehouseMapping;
use App\Models\InvMovement;
use App\Models\PlatformMarketInventory;
use App\Models\PlatformMarketOrder;
use App\Models\StoreWarehouse;
use App\Models\MattelSkuMapping;

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

    public function checkMattelWarehouseInventory($platformMarketOrder,$orginWarehouse)
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
                    $updateObject[$orderItem->seller_sku]["store_id"] = $newWarehouse[$orderItem->seller_sku]["store_id"];
                    $updateObject[$orderItem->seller_sku]["marketplace_sku"] = $newWarehouse[$orderItem->seller_sku]["marketplace_sku"];
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

    public function updatePlatformMarketInventory($updateObjects)
    {
        foreach ($updateObjects as $updateObject) {
           $platformMarketInventory = PlatformMarketInventory::where("store_id",$updateObject["store_id"])
                ->where("warehouse_id",$updateObject["warehouse_id"])
                ->where("marketplace_sku",$updateObject["marketplace_sku"])
                ->first();
            if($platformMarketInventory){
                $remainInventroy = $platformMarketInventory->inventory - $updateObject["qty"];
                $platformMarketInventory->inventory = $remainInventroy;
                $platformMarketInventory->save();
            }
        }
        
    }

    public function checkDuplicateOrder($storeName,$orderNo,$orderId)
    {
        return PlatformMarketOrder::where("platform", $storeName)
                        ->where("platform_order_no", $orderNo)
                        ->where("platform_order_id", "!=", $orderId)
                        ->first();
    }

    public function sendDuplicateOrderMailMessage($storeName,$duplicateOrderNos,$alertEmail)
    {
        $subject = "MarketPlace: [{$storeName}] Order Retrieve Duplicate error!\r\n";
        $message = "These orders are duplicated. Please check it now!\r\n";
        foreach($duplicateOrderNos as $orderNo){
            $message .="(Platform Order No ".$orderNo.") is duplicated\r\n";
        }
        $message .= "Thanks\r\n";
        $this->sendMailMessage($alertEmail, $subject, $message);
    }

    public function getMattleDcSkuByOrder($order)
    {
        $countryCode = strtoupper(substr($order->platform, -2));
        $marketplaceId = strtoupper(substr($order->platform, 0, -2));
        $sellerSku = $order->platformMarketOrderItem->lists("seller_sku");
        $storeWarehouse = StoreWarehouse::where('store_id',$order->store_id)->first();
        $marketplaceSkuMapping = MarketplaceSkuMapping::whereIn("marketplace_sku",$sellerSku)
                                ->where("marketplace_id","=",$marketplaceId)
                                ->where("country_id","=",$countryCode)
                                ->with('merchantProductMapping')
                                ->get();
        foreach ($marketplaceSkuMapping as $value) {
            $mattelSkuMapping = MattelSkuMapping::where("mattel_sku",$value->merchantProductMapping->merchant_sku)
                            ->where('warehouse_id',$storeWarehouse->warehouse_id)
                            ->first();
            $result[$value->marketplace_sku] = $mattelSkuMapping->dc_sku;
        }
        return $result;
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
