<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\Schedule;
use App\Models\PlatformOrderFeed;
use App\Models\So;
use App\Models\SoShipment;
use Carbon\Carbon;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\MarketplaceSkuMapping;
use App\Models\UserStore;
use App\Models\MarketStore;

class ApiPlatformFactoryService
{
    private $_requestData;

    public function __construct(ApiPlatformInterface $apiPlatformInterface)
    {
        $this->apiPlatformInterface = $apiPlatformInterface;
    }

    public function retrieveOrder($storeName, Schedule $schedule)
    {
        $this->apiPlatformInterface->setSchedule($schedule); //set base schedule
        return $this->apiPlatformInterface->retrieveOrder($storeName);
    }

    public function getOrder($storeName)
    {
        $orderId = '62141';

        return $order = $this->apiPlatformInterface->getOrder($storeName, $orderId);
    }

    public function getOrderList($storeName, Schedule $schedule)
    {
        $this->apiPlatformInterface->setSchedule($schedule); //set base schedule
        return $this->apiPlatformInterface->getOrderList($storeName);
    }

    public function getOrderItemList($storeName)
    {
        $orderId = '4274384';

        return $this->apiPlatformInterface->getOrderItemList($storeName, $orderId);
    }

    public function submitOrderFufillment($apiName)
    {
        $bizType = $this->apiPlatformInterface->getPlatformId($apiName);
        $platformOrderIdList = $this->getPlatformOrderIdList($bizType);
        $esgOrders = $this->getEsgOrders($platformOrderIdList);
        $orderFufillmentByGroup = ["Fnac"];
        if($esgOrders){
            if(in_array($bizType, $orderFufillmentByGroup)){
                $this->submitOrderFufillmentByGroup($esgOrders,$platformOrderIdList);
            }else{
                $this->submitOrderFufillmentOneByOne($esgOrders,$platformOrderIdList);
            }
        }
    }
    //1 post one by one 
    public function submitOrderFufillmentOneByOne($esgOrders,$platformOrderIdList)
    {
        foreach ($esgOrders as $esgOrder) {
            $esgOrderShipment = SoShipment::where('sh_no', '=', $esgOrder->so_no.'-01')->where('status', '=', '2')->first();
            if ($esgOrderShipment) {
                $response = $this->apiPlatformInterface->submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList);
                if ($response == true) {
                    $orderState = $this->apiPlatformInterface->getShipedOrderState();
                    $this->updateEsgMarketOrderStatus($esgOrder,$orderState);
                    if ($bizType == 'Amazon') {
                        $this->updateOrCreatePlatformOrderFeed($esgOrder, $platformOrderIdList, $response);
                    }
                }
            }
        }
    }
    
    //2 post all data once
    public function submitOrderFufillmentByGroup($esgOrders,$platformOrderIdList)
    {   
        $xmlData = null;
        $esgOrderGroups = $esgOrders->groupBy("platform_id");
        foreach ($esgOrderGroups as $esgOrderGroup) {
            foreach ($esgOrderGroup as $esgOrder) {
                $esgOrderShipment = SoShipment::where('sh_no', '=', $esgOrder->so_no.'-01')->where('status', '=', '2')->first();
                if ($esgOrderShipment) {
                    $xmlData = $this->apiPlatformInterface->setOrderFufillmentXmlData($esgOrder, $esgOrderShipment);
                }
                $storeName = $platformOrderIdList[$esgOrder->platform_order_id];
            }
            $response = $this->apiPlatformInterface->submitOrderFufillment($storeName,$xmlData);
            if ($response) {
                foreach ($esgOrderGroup as $esgOrder) {
                    $orderState = $this->apiPlatformInterface->getShipedOrderState();
                    if(in_array($esgOrder->platform_order_id, $response)){
                        $this->updateEsgMarketOrderStatus($esgOrder,$orderState);
                    }
                }
            }
        }  
    }

    private function updateEsgMarketOrderStatus($esgOrder,$orderState)
    {
        try {
            $this->markSplitOrderShipped($esgOrder);
            $this->markPlatformMarketOrderShipped($esgOrder->platform_order_id,$orderState);
        } catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
        }
    }

    public function updatePendingPaymentStatus($storeName)
    {
        return $this->apiPlatformInterface->updatePendingPaymentStatus($storeName);
    }

    public function merchantOrderAllocatedReadyToShip()
    {   
        $storeName = $this->getCurrentUserStoreName();
        $platformMarketOrders = $this->allocatedPlatformMarketOrders();
        return $this->merchantOrderReadyToShip($platformMarketOrders);
    }

    public function merchantOrderFufillmentReadyToShip($orderIds)
    {
        $platformMarketOrders = $this->getPlatformMarketOrders($orderIds);
        return $this->merchantOrderReadyToShip($platformMarketOrders);
    }

    public function merchantOrderReadyToShip($platformMarketOrders)
    {   
        if(!$platformMarketOrders->isEmpty()) {
            $platformMarketOrderGroups = $platformMarketOrders->groupBy('platform');
            foreach($platformMarketOrderGroups as $platform => $platformMarketOrderGroup){
                $warehouse = $this->getWarehouseByPlatform($platform,$platformMarketOrderGroup);
                $returnData[$platform] = $this->apiPlatformInterface->orderFufillmentReadyToShip($platformMarketOrderGroup,$warehouse);
            }
            return $result = array("status" => "success","message" => $returnData); 
        }else{
            return $result = array("status" => "failed","message" => "Invalid Order");
        }
    }

    public function merchantOrderFufillmentGetDocument($orderIds,$doucmentType)
    {   
        $platformMarketOrders = $this->getPlatformMarketOrders($orderIds);
        if(!$platformMarketOrders->isEmpty()) {
            $platformMarketOrderGroups = $platformMarketOrders->groupBy('platform');
            $document = $this->apiPlatformInterface->merchantOrderFufillmentGetDocument($platformMarketOrderGroups,$doucmentType);
            return $result = array("status"=> "success","document" => $document);
        }else{
            return $result = array("status"=> "failed","message" => "Invalid Order");
        }
    }

    public function getOrderFufillmentPickingList($orderIds)
    {
        $platformMarketOrders = $this->getPlatformMarketOrders($orderIds);
        $result = null;
        foreach ($platformMarketOrders as $platformMarketOrder) {
            $countryCode = strtoupper(substr($platformMarketOrder->platform, -2));
            $marketplaceId = strtoupper(substr($platformMarketOrder->platform, 0, -2));
            $orderItems = $platformMarketOrder->platformMarketOrderItem;
            $item = null;
            foreach ($orderItems as $orderItem) {
                if(isset($item[$orderItem->seller_sku]["qty"])){
                    $item[$orderItem->seller_sku]["qty"] .= $orderItem->quantity_ordered;
                }else{
                    $marketplaceProduct = MarketplaceSkuMapping::join("product","product.sku",'=',"marketplace_sku_mapping.sku")
                                        ->where("marketplace_sku","=",$orderItem->seller_sku)
                                        ->where("marketplace_id","=",$marketplaceId)
                                        ->where("country_id","=",$countryCode)
                                        ->select("product.name","marketplace_sku_mapping.sku")
                                        ->first();
                    $item[$orderItem->seller_sku]["qty"] = $orderItem->quantity_ordered;
                    $item[$orderItem->seller_sku]["sku"] = $marketplaceProduct->sku;
                    $item[$orderItem->seller_sku]["image"] = "http://shop.eservicesgroup.com//images/product/".$marketplaceProduct->sku."_s.jpg";
                    $item[$orderItem->seller_sku]["product_name"] = $marketplaceProduct->name;
                }
            }
            $result[$platformMarketOrder->platform_order_no] = $item;
        }
        return $result;
    }

    public function setMerchantOrderCanceled($orderIds,$orderParam)
    {
        $platformMarketOrders = $this->getPlatformMarketOrders($orderIds);
        if(!$platformMarketOrders->isEmpty()) {
            $platformMarketOrderGroups = $platformMarketOrders->groupBy("platform");
            foreach($platformMarketOrderGroups as $storeName => $platformMarketOrderGroup){
                 foreach ($platformMarketOrderGroup as $platformMarketOrder) {
                    foreach($platformMarketOrder->platformMarketOrderItem as $orderItem){
                        $orderParam["orderItemId"] = $orderItem->order_item_id;
                        $result[$orderItem->order_item_id] = $this->apiPlatformInterface->setStatusToCanceled($storeName, $orderParam);
                    }
                 }
            }
            return $result;
        }
    }

    public function setMerchantOrderToShipped($trackingNo)
    {
        $storeName = $this->getCurrentUserStoreName();
        $orderItems = PlatformMarketOrderItem::where("tracking_code",$trackingNo)->get();
        if(!$orderItems->isEmpty()){
            foreach ($orderItems as $orderItem) {
                $orderItem->update(array("status"=>"Shipped"));
                $platformOrderId = $orderItem->platform_order_id;
            }
            $object["order_status"] = "Shipped";
            $object["esg_order_status"] = 6;
            PlatformMarketOrder::where("platform_order_id",$platformOrderId)->update($object);
            //remove warehoure retreive num;
        }
    }

    public function setStatusToCanceled($soNoList, $orderParam)
    {
        $extItemCd = So::join("so_item","so.so_no","so_item.so_no")
                ->whereIn('so_no', $soNoList)
                ->select("so_item.ext_item_cd")
                ->first();
        $orderItemIds = array_filter(explode("||",$extItemCd));
        $orderParam["orderItemId"] = $orderItemIds[0];
        return $this->apiPlatformInterface->setStatusToCanceled($storeName, $orderParam);
    }

    public function setStatusToShipped($storeName, $orderItemId)
    {
        return $this->apiPlatformInterface->setStatusToShipped($storeName, $orderItemId);
    }

    public function alertSetOrderReadyToShip($storeName)
    {
        $platformOrderIdList = $this->apiPlatformInterface->alertSetOrderReadyToShip($storeName);
        if($platformOrderIdList){
            $esgOrders = So::whereIn('platform_order_id', $platformOrderIdList)
            ->where('platform_group_order', '=', '1')
            ->where('status', '!=', '0')
            ->get()
            ->map(function ($esgOrder, $key) {
                 $esgOrder->status = $this->getFormatEsgOrderStatus($esgOrder->status);
                 return $esgOrder;
            });
            if(!$esgOrders->isEmpty()){
                $this->apiPlatformInterface->sendAlertMailMessage($storeName,$esgOrders);
            }else{
                return false;
            }
        }
    }

    public function getStoreSchedule($storeName)
    {
        $previousSchedule = Schedule::where('store_name', '=', $storeName)
                            ->where('status', '=', 'C')
                            ->orderBy('last_access_time', 'desc')
                            ->first();
        $currentSchedule = Schedule::create([
            'store_name' => $storeName,
            'status' => 'N',
            // MWS API requested: Must be no later than two minutes before the time that the request was submitted.
            'last_access_time' => Carbon::now()->subMinutes(2),
        ]);
        if (!$previousSchedule) {
            $previousSchedule = $currentSchedule;
        }

        return $previousSchedule;
    }

    private function updateOrCreatePlatformOrderFeed($esgOrder, $platformOrderIdList, $response)
    {
        $platformOrderFeed = PlatformOrderFeed::firstOrNew(['platform_order_id' => $esgOrder->platform_order_id]);
        $platformOrderFeed->platform = $platformOrderIdList[$esgOrder->platform_order_id];
        $platformOrderFeed->feed_type = '_POST_ORDER_FULFILLMENT_DATA_';
        if ($response) {
            $platformOrderFeed->feed_submission_id = $response['FeedSubmissionId'];
            $platformOrderFeed->submitted_date = $response['SubmittedDate'];
            $platformOrderFeed->feed_processing_status = $response['FeedProcessingStatus'];
        } else {
            $platformOrderFeed->feed_processing_status = '_SUBMITTED_FAILED';
        }
        $platformOrderFeed->save();
    }

    private function getPlatformOrderIdList($bizType)
    {
        switch ($bizType) {
            case 'amazon':
                $platformOrderList = PlatformMarketOrder::amazonUnshippedOrder()
                ->leftJoin('platform_order_feeds', 'platform_market_order.platform_order_id', '=', 'platform_order_feeds.platform_order_id')
                ->whereNull('platform_order_feeds.platform_order_id')
                ->select('platform_market_order.*')
                ->get();
                break;
            default:
                $platformOrderList = PlatformMarketOrder::unshippedOrder()->where('biz_type', '=', $bizType)->get();
                break;
        }
        $platformOrderIdList = $platformOrderList->pluck('platform', 'platform_order_no')->toArray();

        return $platformOrderIdList;
    }

    private function getEsgOrders($platformOrderIdList)
    {
        return $esgOrders = So::whereIn('platform_order_id', array_keys($platformOrderIdList))
            ->where('platform_group_order', '=', '1')
            ->where('status', '=', '6')
            ->get();
    }

    private function markSplitOrderShipped($order)
    {
        $splitOrders = So::where('platform_order_id', '=', $order->platform_order_id)
            ->where('platform_split_order', '=', 1)->get();
        $splitOrders->map(function ($splitOrder) use ($order) {
            $splitOrder->dispatch_date = $order->dispatch_date;
            $splitOrder->status = 6;
            $splitOrder->save();
        });
    }

    private function getFormatEsgOrderStatus($status)
    {
        $esgStatus = array(
            "0" => "Inactive",
            "1" => "New",
            "2" => "Paid",
            "3" => "Fulfilment AKA Credit Checked",
            "4" => "Partial Allocated ",
            "5" => "Full Allocated",
            "6" => "Shipped",
        );
        if(isset($esgStatus[$status]))
        return $esgStatus[$status];
    }

    public function markPlatformMarketOrderShipped($orderId,$orderState,$esgOrderStatus='')
    {
        $platformMarketOrder = PlatformMarketOrder::where('platform_order_id', '=', $orderId)
                            ->firstOrFail();
        if ($platformMarketOrder) {
            $platformMarketOrder->order_status = $orderState;
            $platformMarketOrder->esg_order_status = 6;
            $platformMarketOrder->save();
            if ($orderItems = $platformMarketOrder->platformMarketOrderItem()->get()) {
                foreach ($orderItems as $orderItem) {
                    $orderItem->status = $orderState;
                    $orderItem->save();
                }
            }
        }
    }

    public function getPlatformMarketOrders($orderIds)
    {
       return $esgOrderGroup = PlatformMarketOrder::whereIn('id', $orderIds)
                ->get();
    }

    public function allocatedPlatformMarketOrders($platform)
    {
        $esgOrderStatus = array(
            PlatformMarketConstService::ORDER_STATUS_NEW,
            PlatformMarketConstService::ORDER_STATUS_PAID,
            PlatformMarketConstService::ORDER_STATUS_FULFILMENT_CHECKED,
            PlatformMarketConstService::ORDER_STATUS_PENDING,
            PlatformMarketConstService::ORDER_STATUS_UNSHIPPED,
        );
        $platformMarketOrders = PlatformMarketOrder::whereIn("esg_order_status",$esgOrderStatus)->where("platform", "=", $platform)
            ->get();
        return $platformMarketOrders;
    }

    public function getWarehouseByPlatform($platform,$platformMarketOrderGroups)
    {
        $mattelWarehouse = array(
            "MY" => "MATTEL_DC_MY_KT",
            "TH" => "MATTEL_DC_TH_WD",
            "ID" => "MATTEL_DC_ID_EY",
            "SG" => "MATTEL_DC_SG_EY",
            "PH" => "MATTEL_DC_PH_RP",
            "VN" => "MATTEL_DC_VN_PT"
        );
        $warehouse = null;
        $countryCode = strtoupper(substr($platform, -2));
        $marketplaceId = strtoupper(substr($platform, 0, -2));
        $marketplaceProducts = MarketplaceSkuMapping::join("inventory","inventory.prod_sku","=","marketplace_sku_mapping.sku")
                    ->where("marketplace_id","=",$marketplaceId)
                    ->where("country_id","=",$countryCode)
                    ->whereIn("warehouse_id",$mattelWarehouse[$countryCode])
                    ->pluck("sku","marketplace_sku","inventory.inventory","inventory.retrieve")
                    ->get()
                    ->toArray();
        foreach($marketplaceProducts as $marketplaceProduct){
            $warehouse[$marketplaceProduct["marketplace_sku"]]= $marketplaceProduct;
        }
        return $warehouse;
    }

    private function getCurrentUserStoreName()
    {
        $userId = \Authorizer::getResourceOwnerId();
        $marketStoreId = UserStore::find("id",$userId)->market_store_id;
        $storeName = MarketStore::find("id",$marketStoreId)->store_name;
        return $storeName;
    }
    
}
