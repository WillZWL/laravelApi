<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Inventory;
use App\Models\Schedule;
use App\Models\InvMovement;
use App\Models\So;
use App\Models\SoShipment;
use App\Models\EsgLgsOrderDocumentLog;
use App\Models\PlatformMarketReasons;
use App\Models\StoreWarehouse;
use PDF;
use Excel;
use Zipper;
use Config;

//use lazada api package
use App\Repository\LazadaMws\LazadaOrder;
use App\Repository\LazadaMws\LazadaOrderList;
use App\Repository\LazadaMws\LazadaOrderItemList;
use App\Repository\LazadaMws\LazadaOrderStatus;
use App\Repository\LazadaMws\LazadaDocument;
use App\Repository\LazadaMws\LazadaShipmentProviders;
use App\Repository\LazadaMws\LazadaMultipleOrderItems;
use App\Repository\LazadaMws\LazadaProductList;
use App\Repository\LazadaMws\LazadaFailureReasons;

class ApiLazadaService implements ApiPlatformInterface
{
    use ApiBaseOrderTraitService;
	private $storeCurrency;

    public function __construct()
    {
        $this->stores =  Config::get('lazada-mws.store');
    }

	public function getPlatformId()
	{
		return "Lazada";
	}

	public function retrieveOrder($storeName,$schedule)
    {
        $this->setSchedule($schedule);
		$originOrderList = $this->getOrderList($storeName);
        if($originOrderList){
        	foreach($originOrderList as $order){
                if(isset($order["OrderItems"])){
    				if (isset($order['AddressShipping'])) {
    					$addressId = $this->updateOrCreatePlatformMarketShippingAddress($order,$storeName);
    				}
				    $platformMarketOrder = $this->updateOrCreatePlatformMarketOrder($order,$addressId,$storeName);
					foreach($order["OrderItems"] as $orderItems){
						if(isset($orderItems["OrderItemId"])){
                            $this->updateOrCreatePlatformMarketOrderItem($platformMarketOrder->id, $order, $orderItems, $storeName);
                        }else{
                            foreach ($orderItems as $orderItem) {
                                $this->updateOrCreatePlatformMarketOrderItem($platformMarketOrder->id, $order, $orderItem, $storeName);
                            }
                        }
					}
				}
			}
            return true;
        }
	}

	public function getOrder($storeName,$orderId)
	{
		$this->lazadaOrder = new LazadaOrder($storeName);
		$this->storeCurrency = $this->lazadaOrder->getStoreCurrency();
		$this->lazadaOrder->setOrderId($orderId);
		$returnData = $this->lazadaOrder->fetchOrder();
		return $returnData;
	}

	public function getOrderList($storeName)
	{
        $newOrderList = null;
		$this->lazadaOrderList=new LazadaOrderList($storeName);
		$this->storeCurrency=$this->lazadaOrderList->getStoreCurrency();
		$dateTime = date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
		$this->lazadaOrderList->setUpdatedAfter($dateTime);
		$originOrderList = $this->lazadaOrderList->fetchOrderList();
        $this->saveDataToFile(serialize($originOrderList),"getOrderList");
        if($originOrderList){
            $duplicateOrderNo = null;$orderIdList = null;
            foreach ($originOrderList as $order) {
                $result = $this->checkDuplicateOrder($storeName,$order["OrderNumber"],$order['OrderId']);
                if($result){
                    $duplicateOrderNo[] = $order['OrderNumber'];
                }else{
                    $orderIdList[] = $order['OrderId'];
                    $newOrderList[$order['OrderId']] = $order;
                }
            }
            if($duplicateOrderNo){
                $alertEmail = $this->stores[$storeName]["userId"];
                $this->sendDuplicateOrderMailMessage($storeName,$duplicateOrderNo,$alertEmail);
            }
            if($orderIdList){
                $multipleOrderItems = $this->getMultipleOrderItems($storeName,$orderIdList);
                if($multipleOrderItems){
                    foreach ($multipleOrderItems as $orderItems) {
                    $newOrderList[$orderItems["OrderId"]]["OrderItems"] = $orderItems["OrderItems"];
                    }
                    return $newOrderList;
                }
            }
        }
	}

    public function getPendingOrderList($storeName)
    {
        $this->lazadaOrderList=new LazadaOrderList($storeName);
        $this->lazadaOrderList->setStatus("pending");
        $originOrderList=$this->lazadaOrderList->fetchOrderList();
        return $originOrderList;
    }

	public function getOrderItemList($storeName,$orderId)
	{
		$this->lazadaOrderItemList = new LazadaOrderItemList($storeName);
		$this->lazadaOrderItemList->setOrderId($orderId);
		$orginOrderItemList=$this->lazadaOrderItemList->fetchOrderItemList();
		$this->saveDataToFile(serialize($orginOrderItemList),"getOrderItemList");
        return $orginOrderItemList;
	}

    //run request 4px to lazada api set order ready to ship one by one
    public function iwmsSetLgsOrderReadyToShip($esgOrder, $getTrackingNo = true)
    {
        $orderList = null; $valid = null; $trackingNo = null;
        $prefix = strtoupper(substr($esgOrder->platform_id,3,2));
        $countryCode = strtoupper(substr($esgOrder->platform_id, -2));
        $storeName = $prefix."LAZADA".$countryCode;
        if(!empty($esgOrder->platformMarketOrder)){
            if(in_array($esgOrder->platformMarketOrder->status, ["Shipped","ReadyToShip","Delivered"])){
                $result["valid"] = 1; 
            }else{
                $result = $this->setEsgLgsOrderReadyToShip($esgOrder); 
            }
        }
        if($getTrackingNo){
            $orderList = $this->getMultipleOrderItems($storeName, [$esgOrder->platformMarketOrder->platform_id]);
            //Not allowed to change the preselected shipment provider
            if(!empty($orderList)){
                foreach ($orderList as $order) {
                    foreach ($order["OrderItems"] as $orderItem) {
                        if(isset($orderItem["TrackingCode"])){
                            $trackingNo = $orderItem["TrackingCode"];
                        }else{
                            $trackingNo = $orderItem["0"]["TrackingCode"];
                        }
                    }
                }   
            }  
        }
        return array("tracking_no" => $trackingNo, "valid" => $result["valid"]);
    }

    //ESG SYSTEM SET ORDER TO READYSHIP AND GET DOCUMENT
    public function cronSetEsgLgsOrderToReadyToShip()
    {
        $document = null; $msg = null;
        $esgOrders = $this->getEsgLgsAllocateOrders();
        if(!$esgOrders->isEmpty()) {
            foreach ($esgOrders as $esgOrder) {
                $result = null;
                if(!empty($esgOrder->platformMarketOrder)){
                    if(in_array($esgOrder->platformMarketOrder->status, ["Shipped","ReadyToShip","Delivered"])){
                        $document[$esgOrder->platformMarketOrder->platform][$esgOrder->so_no] = $esgOrder->txn_id;
                    }else{
                        $result = $this->setEsgLgsOrderReadyToShip($esgOrder); 
                        if($result["valid"]){
                            $this->updateEsgToShipOrderStatusToDispatch($esgOrder);
                            $document[$result["storeName"]][$esgOrder->so_no] = $result["orderItemId"];
                        } else {
                            $msg .= "Order NO: ".$esgOrder->so_no." set ready to ship failed.\r\n";
                        }
                    }
                }
            }
            if(!empty($msg)){
                $subject = "lazada Order Ready to ship failed";
                $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
                mail("jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
            }
            if(!empty($document)){
                //$this->backupEsgOrderDocument($document,$batchId);
                $esgLgsOrderLog = $this->getEsgLgsOrderDocumentLabel($document);
                foreach ($esgLgsOrderLog["DocumentLogs"] as $documentLog) {
                   $esgLgsOrderDocumentLog = new EsgLgsOrderDocumentLog();
                   $esgLgsOrderDocumentLog->store_name = $documentLog["store_name"];
                   $esgLgsOrderDocumentLog->order_item_ids = json_encode($documentLog["order_item_ids"]);
                   $esgLgsOrderDocumentLog->document_type = $documentLog["document_type"];
                   $esgLgsOrderDocumentLog->status = $documentLog["status"];
                   $esgLgsOrderDocumentLog->document_url = $esgLgsOrderLog["documentUrl"];
                   $esgLgsOrderDocumentLog->save();
                }
            }
        }
    }

    public function cornGetEsgLgsOrderDocument()
    {
        $esgLgsOrderDocumentLogs = EsgLgsOrderDocumentLog::where("status",0)
            ->get();
        if(!$esgLgsOrderDocumentLogs->isEmpty()){
            foreach ($esgLgsOrderDocumentLogs as $esgLgsOrderDocumentLog) {
            $document[$esgLgsOrderDocumentLog->store_name] = json_decode($esgLgsOrderDocumentLog->order_item_ids);
                if(!empty($document)){
                    $esgLgsOrderLog = $this->getEsgLgsOrderDocumentLabel($document);
                    foreach ($esgLgsOrderLog["DocumentLogs"] as $documentLog) {
                       $esgLgsOrderDocumentLog->status = $documentLog["status"];
                       $esgLgsOrderDocumentLog->document_url = $esgLgsOrderLog["documentUrl"];
                       $esgLgsOrderDocumentLog->save();
                    }
                }
            }
        }
    }

    private function getGroupStoreNameDocument($documentArr)
    {
        $document = null;
        if(!empty($documentArr)){
            foreach ($documentArr as $storeName => $documentValue) {
                foreach ($documentValue as $value) {
                    $documentLabel[]= $value;
                }
                $document[$storeName] = array_unique($documentLabel);
            }
        }
        return $document;
    }

    private function getEsgLgsAllocateOrders()
    {
        $this->fromData = date("Y-m-d 00:00:00");
        $this->toDate = date("Y-m-d 23:59:59");
        return $esgOrders = So::where("status",5)
            ->where("refund_status", "0")
            ->where("hold_status", "0")
            ->where("prepay_hold_status", "0")
            ->whereIn("esg_quotation_courier_id", ["93","130"])
            ->whereHas('soAllocate', function ($query) {
                $query->whereIn('warehouse_id', ["ES_HK"])
                    ->where("status", 1)
                    ->where("modify_on", ">=", $this->fromData)
                    ->where("modify_on", "<=", $this->toDate);
            })
            ->with("soItem")
            ->get();
    }

    private function setEsgLgsOrderReadyToShip($esgOrder)
    {
        $valid = null; $orderItemIds = array();
        $prefix = strtoupper(substr($esgOrder->platform_id,3,2));
        $countryCode = strtoupper(substr($esgOrder->platform_id, -2));
        $storeName = $prefix."LAZADA".$countryCode;
        $lazadaShipments = $this->getShipmentProviders($storeName);
        $warehouseId = $esgOrder->soAllocate->first()->warehouse_id;
        $shipmentProvider = $this->getEsgShippingProvider($warehouseId,$countryCode,$lazadaShipments);
        if(!empty($shipmentProvider)){
            foreach($esgOrder->soItem as $soItem){
                $itemIds = array_filter(explode("||",$soItem->ext_item_cd));
                foreach($itemIds as $itemId){
                    $orderItemIds[] = $itemId;
                }
            }
            $result = $this->setLgsOrderStatusToReadyToShip($storeName,$orderItemIds,$shipmentProvider);
            if($result){
                $valid = true;
            }
        }else{
            $subject = "lazada shipmentProvider need mapping";
            $msg = print_r($lazadaShipments, true);
            $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
            mail("jimmy.gao@eservicesgroup.com", $subject, $msg, $header);
            $valid = false;
        }
        return [
            "storeName" => $storeName, 
            "orderItemId" => $orderItemIds[0],
            "valid" => $valid,
            ];
    }

    private function alertEsgOrderReadyToShipEmail($subject, $message)
    {
        $header = "From: admin@shop.eservciesgroup.com".PHP_EOL;
        mail("jimmy.gao@eservicesgroup.com", $subject, $message, $header);
        $valid = false;
    }

    private function updateEsgToShipOrderStatusToDispatch($esgOrder)
    {
        $soShipment = $this->createEsgSoShipment($esgOrder);
        if(!empty($soShipment)){
            foreach ($esgOrder->soAllocate as $soAllocate) { 
                if($soAllocate->status != 1){
                    continue;
                }
                $invMovement = InvMovement::where("ship_ref", $soAllocate->id)
                    ->where("status", "AL")
                    ->first();
                if(!empty($invMovement)){
                    $invMovement->ship_ref = $soShipment->sh_no;
                    $invMovement->status = "OT";
                    $invMovement->save();
                    $soAllocate->status = 2;
                    $soAllocate->sh_no = $soShipment->sh_no;
                    $soAllocate->save();
                }
            }
        }
    }

    private function createEsgSoShipment($esgOrder)
    {
        $soShipment = SoShipment::where("sh_no", $esgOrder->so_no."-01")->first();
        if(!empty($soShipment)){
            return null;
        }else{
            $object['sh_no'] = $esgOrder->so_no."-01";
            $object['courier_id'] = $esgOrder->esg_quotation_courier_id;
            $object['status'] = 1;
            $soShipment = SoShipment::updateOrCreate(['sh_no' => $object['sh_no']],$object);
            return $soShipment;
        }
    }

    public function orderFufillmentReadyToShip($orderGroup,$warehouse)
    {
        $returnData = array();$warehouseInventory = null;
        foreach($orderGroup as $order){
            if($order->esg_order_status != 6){
                if(!$this->checkPlatformMarketUnshippedOrder($order)){
                   $returnData[$order->platform_order_no] = "The order status has been setted in lazada sell system\r\n";
                   continue;
                }
                if(isset($warehouseInventory["warehouse"])){
                    $warehouse = $warehouseInventory["warehouse"];
                }
                $warehouseInventory = $this->checkMattelWarehouseInventory($order,$warehouse);
                if($warehouseInventory["updateObject"]){
                    $orderItemIds = array();
                    foreach($order->platformMarketOrderItem as $orderItem){
                        if($orderItem->status === "Pending"){
                            $orderItemIds[] = $orderItem->order_item_id;
                        }
                    }
                    $shipmentProvider = $this->getMettelShipmentProvider($order->platform);
                    if($shipmentProvider && $orderItemIds){
                        $result = $this->setApiOrderReadyToShip($order->platform,$orderItemIds,$shipmentProvider,$order->platform_order_id);
                        if($result){
                            $orderIdList[] = $order->platform_order_id;
                            $this->updateOrderStatusReadyToShip($order->platform,$orderIdList);
                            $this->updatePlatformMarketInventory($warehouseInventory["updateObject"]);
                            $returnData[$order->platform_order_no] = " Set Ready To Ship Success\r\n";
                        }else{
                            $returnData[$order->platform_order_no] = " Set Ready To Ship Failed\r\n";
                        }
                    }else{
                        $returnData[$order->platform_order_no] = "Shipment Provider is not exit in lazada admin system.";
                    }
               }else{
                    $returnData[$order->platform_order_no] = " Has no inventory.";
               }
            }
        }
        return $returnData;
    }

    public function merchantOrderFufillmentGetDocument($orderGroups,$doucmentType)
    {
        $orderItemIds = array();$fileDate = date("h-i-s");
        $filePath = \Storage::disk('merchant')->getDriver()->getAdapter()->getPathPrefix();
        $pdfFilePath = $filePath.date("Y")."/".date("m")."/".date("d")."/label/";
        $documentFile = ""; $mattelDcSkuList = array();
        foreach($orderGroups as $storeName => $orderGroup){
            foreach($orderGroup as $order){
                $orderItem = $order->platformMarketOrderItem->first();
                $orderItemIds[] = $orderItem->order_item_id;
                if($doucmentType == "invoice"){
                    $mattelDcSkuList[] = $this->getMattleDcSkuByOrder($order);
                }
            }
            $documentFile .= $this->getDocument($storeName,$orderItemIds,$doucmentType);
        }
        if($documentFile){
            $documentFile = preg_replace(array('/transform:rotate/'), array('-webkit-transform:rotate'), $documentFile);
            $documentFile = $this->getFormatDocumentFile($documentFile,$doucmentType,$mattelDcSkuList);
            $file = $doucmentType.$fileDate.'.pdf';
            PDF::loadHTML($documentFile)->setOption('margin-top', 5)->setOption('margin-bottom', 5)->setOption("encoding","UTF-8")->save($pdfFilePath.$file);
            $pdfFile = url("api/merchant-api/download-label/".$file);
            return $pdfFile;
        }
    }

    private function backupEsgLgsOrderDocument($document, $batchId)
    {
        $document[$result["storeName"]][$esgOrder->so_no] = $result["orderItemId"];
        foreach ($document as $storeName => $orderDocument) {
           foreach ($orderDocument as $soNo => $value) {
              $esgLgsOrderDocumentLog = new EsgLgsOrderDocumentLog();
              $esgLgsOrderDocumentLog->batch_id = $batchId;
              $esgLgsOrderDocumentLog->store_name = $storeName;
              $esgLgsOrderDocumentLog->so_no = $soNo;
              $esgLgsOrderDocumentLog->order_item_ids = json_encode($value);
              $esgLgsOrderDocumentLog->save();
           }
        }
    }

    private function getEsgLgsOrderDocumentLabel($documentLabels)
    {
        $pdfFilePath = "/var/data/vanguard/courier/LGS/".date("Y")."/".date("m")."/".date("d")."/";
        $document = array(); $esgLgsOrderLog = null;
        //$style ='<style type="text/css">.page {overflow: hidden;page-break-inside: avoid;}}</style>';
        //
        $pdfHrDom ='<hr style="page-break-after: always;border-top: 3px dashed;">';
        $doucmentTypeArr = ["invoice","carrierManifest","shippingLabel"];
        $documentUrl = null;
        foreach ($documentLabels as $storeName => $orderItemId) {    
            $esgLgsOrderDocumentLog["document_type"] = json_encode($doucmentTypeArr);
            $esgLgsOrderDocumentLog["store_name"] = $storeName;
            $esgLgsOrderDocumentLog["order_item_ids"] = json_encode($orderItemId);
            $status = 1;
            foreach($doucmentTypeArr as $doucmentType ){
                $fileHtml = $this->getDocument($storeName, $orderItemId, $doucmentType);
                if($fileHtml){
                    if($doucmentType == "invoice"){
                    $fileHtml = preg_replace(array('/class="logo"/'), array('class="page"'), $fileHtml,2);
                    }
                    if(isset($document[$doucmentType])){
                        $document[$doucmentType] .= $pdfHrDom.$fileHtml;
                    }else{
                        $document[$doucmentType] = $fileHtml;
                    }
                }else{
                    $status = 0;
                }
            }
            $esgLgsOrderDocumentLog["status"] = $status;
            $esgLgsOrderLog["DocumentLogs"][] = $esgLgsOrderDocumentLog;
        }
        if($document){
          $documentUrl = $this->getDocumentSaveToDirectory($document,$pdfFilePath);
        }
        $esgLgsOrderLog["documentUrl"] = $documentUrl;
        return $esgLgsOrderLog;
    }

    private function updateEsgWarehouseInventory($updateWarehouseObject)
    {
        foreach ($updateWarehouseObject as $esgOrder) {
            $warehouseId = $esgOrder->soAllocate->first()->warehouse_id;
            foreach ($esgOrder->soItem as $soItem) {
                $object = array(
                    "ship_ref" => $esgOrder->so_no."-01",
                    "sku" => $soItem->prod_sku,
                    "qty" => $soItem->qty,
                    "type" => "C",
                    "from_location" => $warehouseId,
                    "reason" => "ESG LAZADA READY TO SHIP",
                    "status" => "OT"
                );
                $invMovement = InvMovement::updateOrCreate(
                    [
                        'ship_ref' => $esgOrder->so_no."-01",
                        'sku' => $soItem->prod_sku,
                    ],
                    $object
                );
            }
        }
    }

    private function checkEsgOrderInventory($warehouseId,$esgOrder)
    {
        $orderItemIds = array();
        foreach($esgOrder->soItem as $soItem){
            if($warehouseId){
                $inventory = Inventory::where("warehouse_id",$warehouseId)
                    ->where("prod_sku",$soItem->prod_sku)
                    ->first();
                $remain = $inventory->inventory - $soItem->qty;
                if($remain < 0){ return false;}
            }
            $itemIds = array_filter(explode("||",$soItem->ext_item_cd));
            foreach($itemIds as $itemId){
                $orderItemIds[] = $itemId;
            }
        }
        return $orderItemIds;
    }

    private function setLgsOrderStatusToReadyToShip($storeName,$orderItemIds,$shipmentProvider)
    {
        $responseResult = null;
        $itemObject = array("orderItemIds" => $orderItemIds, "ShipmentProvider" => $shipmentProvider);
        $marketplacePacked = $this->setStatusToPackedByMarketplace($storeName,$orderItemIds,$shipmentProvider);
        $responseResult = $this->setStatusToReadyToShip($storeName,$itemObject);
        if($responseResult){
            if(isset($responseResult["PurchaseOrderId"])){
                return true;
            }else{
                $result = true;
                foreach ($responseResult as $response) {
                   if(!isset($response["PurchaseOrderId"])){
                        $result = false;
                   }
                }
                return $result;
            }
        }
    }

    private function setApiOrderReadyToShip($storeName,$orderItemIds,$shipmentProvider,$orderId)
    {
        $responseResult = null;
        if ($orderItemIds) {
            $itemObject = array("orderItemIds" => $orderItemIds);
            $marketplacePacked = $this->setStatusToPackedByMarketplace($storeName,$orderItemIds,$shipmentProvider);
            $countryCode = strtoupper(substr($storeName, -2));
            if(in_array($countryCode, array("TH","SG","ID"))){
                $orderList = $this->getMultipleOrderItems($storeName,[$orderId]);
                //Not allowed to change the preselected shipment provider
                foreach ($orderList as $order) {
                    foreach ($order["OrderItems"] as $orderItem) {
                        if(isset($orderItem["TrackingCode"])){
                            $itemObject["TrackingNumber"] = $orderItem["TrackingCode"];
                        }else{
                            $itemObject["TrackingNumber"] = $orderItem["0"]["TrackingCode"];
                        }
                        $itemObject["ShipmentProvider"] = $shipmentProvider;
                    }
                }
            }
            $responseResult = $this->setStatusToReadyToShip($storeName,$itemObject);
            if($responseResult){
                if(isset($responseResult["PurchaseOrderId"])){
                    return true;
                }else{
                    $result = true;
                    foreach ($responseResult as $response) {
                       if(!isset($response["PurchaseOrderId"])){
                            $result = false;
                       }
                    }
                    return $result;
                }
            }
        }
    }

    public function updateOrderStatusReadyToShip($storeName,$orderIdList)
    {
        $orderList = $this->getMultipleOrderItems($storeName,$orderIdList);
        if($orderList){
            foreach($orderList as $order){
                $orderObject = array(
                    'order_status' => "ReadyToShip",
                    'esg_order_status' => $this->getSoOrderStatus("ReadyToShip")
                    );
                PlatformMarketOrder::where("platform_order_id",$order['OrderId'])->update($orderObject);
                So::where('platform_order_id',$order['OrderNumber'])->update(['status' => 5]);
                foreach($order["OrderItems"] as $orderItem){
                    if(isset($orderItem["OrderItemId"])){
                        $orderItemIds[] = $orderItem["OrderItemId"];
                        $object = array(
                            'shipment_provider' => $orderItem["ShipmentProvider"],
                            'tracking_code' => $orderItem["TrackingCode"],
                            'status' => $orderItem["Status"]
                        );
                    }else{
                        foreach ($orderItem as $item) {
                            $orderItemIds[] = $item["OrderItemId"];
                            $object = array(
                                'shipment_provider' => $item["ShipmentProvider"],
                                'tracking_code' => $item["TrackingCode"],
                                'status' => $item["Status"]
                            );
                        }
                    }
                    PlatformMarketOrderItem::where("platform_order_id",$order['OrderId'])
                                    ->whereIn('order_item_id',$orderItemIds)
                                    ->update($object);
                }
            }
        }
    }

    public function exportTrackinNoCsvToDirectory($storeName,$orderList)
    {
        $filePath = "/var/data/shop.eservicesgroup.com/lazada/tracking/".date("Y")."/".date("m")."/".date("d")."/";
        $cellData[] = array('Marketplace', 'ESG SKU', 'SellerSku', 'OrderId', 'OrderItemId', 'Currency', 'ItemPrice', 'PaidPrice', 'TaxAmount', 'Name', 'PurchaseOrderNumber', 'PurchaseOrderId', 'PackageId');
        foreach($orderList as $order){
            foreach($order["OrderItems"]["OrderItem"] as $orderItem){
                //$orderItem["TrackingCode"];
                $cellRow = array(
                    'marketplace_id' => $storeName,
                    'sku' => $orderItem["Sku"],
                    'marketplace_sku' => $orderItem["Sku"],
                    'OrderId' => $orderItem["OrderId"],
                    'OrderItemId' => $orderItem["OrderItemId"],
                    'Currency' => $orderItem["Currency"],
                    'ItemPrice' => $orderItem["ItemPrice"],
                    'PaidPrice' => $orderItem["PaidPrice"],
                    'TaxAmount' => $orderItem["TaxAmount"],
                    'Name' => $orderItem["Name"],
                    'PurchaseOrderNumber' => $orderItem["PurchaseOrderNumber"],
                    'PurchaseOrderId' => $orderItem["PurchaseOrderId"],
                    'PackageId' => $orderItem["PackageId"],
                );
                $cellData[] = $cellRow;
            }
        }
        //Excel文件导出功能
        Excel::create('LazadaOrderTrackingNo', function ($excel) use ($cellData) {
            $excel->sheet('OrderTrackingNo', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->store('csv',$filePath);
    }

    private function getDocumentSaveToDirectory($document, $pdfFilePath)
    {
        $documentPdf = array();
        $fileDate = date("h-i-s");
        if (!file_exists($pdfFilePath)) {
            mkdir($pdfFilePath, 0755, true);
        }
        foreach($document as $documentType => $documentFile){
            if($documentFile){
                $file = $pdfFilePath.$documentType.$fileDate.'.pdf';
                PDF::loadHTML($documentFile)->setOption("encoding","UTF-8")->save($file);
                $documentPdf[$documentFile] = $file;
            }
        }
        if($documentPdf) {
            $fileName ='readyToShipLabel'.date("H-i-s").'.zip';
            Zipper::make($pdfFilePath.$fileName)->add($documentPdf)->close();
            $zipperFile = url("api/lazada-api/donwload-label/".$fileName);
            return $zipperFile;
        }
    }

    public function getShipmentProviders($storeName)
    {
        $this->lazadaShipmentProviders=new LazadaShipmentProviders($storeName);
        $result = $this->lazadaShipmentProviders->fetchShipmentProviders();
        return $result;
    }

	public function setStatusToCanceled($storeName,$orderParam)
	{
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemId($orderParam["orderItemId"]);
        if(isset($orderParam["reason"]))
		$this->lazadaOrderStatus->setReason($orderParam["reason"]);
        if(isset($orderParam["reasonDetail"]))
		$this->lazadaOrderStatus->setReasonDetail($orderParam["reasonDetail"]);
		$result = $this->lazadaOrderStatus->setStatusToCanceled();
		return $this->checkResultData($result,$this->lazadaOrderStatus);
	}

    public function updateOrCreatePlatformMarketReasons($storeName)
    {
        $this->lazadaFailureReasons = new LazadaFailureReasons($storeName);
        $failureReasons = $this->lazadaFailureReasons->fetchFailureReasons();
        $platformStore = $this->getPlatformStore($storeName);
        if($failureReasons){
            foreach ($failureReasons as $failureReason) {
                if(isset($failureReason['Type']) && isset($failureReason['Name'])){
                    $object = [
                        'store_id' => $platformStore->id,
                        'type' => $failureReason['Type'],
                        'reason_name' => $failureReason['Name']
                    ];
                    $platformMarketReasons = PlatformMarketReasons::updateOrCreate(
                        [
                            'store_id' => $platformStore->id,
                            'reason_name' => $failureReason['Name']
                        ],
                        $object
                    );
                }
            }
        }
    }

	public function setStatusToPackedByMarketplace($storeName,$orderItemIds,$shipmentProvider)
	{
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemIds($orderItemIds);
		$this->lazadaOrderStatus->setDeliveryType("dropship");
		$this->lazadaOrderStatus->setShippingProvider($shipmentProvider);
		$orginOrderItemList = $this->lazadaOrderStatus->setStatusToPackedByMarketplace();
        return $orginOrderItemList;
	}

    public function getMultipleOrderItems($storeName,$orderIdList)
    {
        $this->lazadaMultipleOrderItems = new LazadaMultipleOrderItems($storeName);
        $this->lazadaMultipleOrderItems->setOrderIdList($orderIdList);
        $orginOrderItemList = $this->lazadaMultipleOrderItems->fetchMultipleOrderItems();
        $this->saveDataToFile(serialize($orginOrderItemList),"fetchMultipleOrderItems");
        return $orginOrderItemList;
    }

    public function getDocument($storeName,$orderItemIds,$documentType)
    {
        //$orderItemIds可以是不同的order中一个orderItemId
        $this->lazadaDocument = new LazadaDocument($storeName);
        $this->lazadaDocument->setDocumentType($documentType);
        $this->lazadaDocument->setOrderItemIds($orderItemIds);
        $documents = $this->lazadaDocument->fetchDocument();
        if($documents){
            foreach($documents as $document){
                if(isset($document["File"]) && $document["DocumentType"] == $documentType){
                    $fileHtml = base64_decode($document["File"]);
                    if($documentType == "carrierManifest"){
                        $assetsUrl ='"'.asset('/')."assets";
                        $newFileHtml = preg_replace(array('/\"\/assets/'), array($assetsUrl), $fileHtml);
                        $fileHtml = $this->getManifestCss().$newFileHtml;
                    }
                    return $fileHtml;
                }
            }
        }
    }

	public function setStatusToReadyToShip($storeName,$itemObject)
	{
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemIds($itemObject["orderItemIds"]);
		$this->lazadaOrderStatus->setDeliveryType("dropship");
        if(isset($itemObject["ShipmentProvider"]))
		$this->lazadaOrderStatus->setShippingProvider($itemObject["ShipmentProvider"]);
        if(isset($itemObject["TrackingNumber"]))
		$this->lazadaOrderStatus->setTrackingNumber($itemObject["TrackingNumber"]);
        $orginOrderItemList = $this->lazadaOrderStatus->setStatusToReadyToShip();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToReadyToShip");
        return $orginOrderItemList;
	}

	public function setStatusToShipped($storeName,$orderId)
	{
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemId($orderItemId);
		$orginOrderItemList=$this->lazadaOrderStatus->setStatusToShipped();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToShipped");
        return $orginOrderItemList;
	}

	public function setStatusToFailedDelivery($storeName,$orderId)
	{
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemId($orderItemId);
		$this->lazadaOrderStatus->setReason("reason");
		$this->lazadaOrderStatus->setReasonDetail("reasonDetail");
		$orginOrderItemList=$this->lazadaOrderStatus->setStatusToFailedDelivery();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToFailedDelivery");
        return $orginOrderItemList;
	}

	public function setStatusToDelivered($storeName,$orderId)
	{
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemId($orderItemId);
		$orginOrderItemList=$this->lazadaOrderStatus->setStatusToDelivered();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToDelivered");
        return $orginOrderItemList;
	}

	//update or insert data to database
	public function updateOrCreatePlatformMarketOrder($order,$addressId,$storeName)
	{
		//orderStatus
		if(is_array($order['Statuses']["Status"])){
			$orderStatus=implode("||",$order['Statuses']["Status"]);
		}else{
			$orderStatus=studly_case($order['Statuses']["Status"]);
		}
        $platformStore = $this->getPlatformStore($storeName);
		$object = [
            //'platform' => $storeName,
            'biz_type' => "Lazada",
            'store_id' => $platformStore->id,
            'platform_order_id' => $order['OrderId'],
            'platform_order_no' => $order['OrderNumber'],
            'purchase_date' => $order['CreatedAt'],
            'last_update_date' => $order['UpdatedAt'],
            'order_status' => $orderStatus,
            'esg_order_status'=>$this->getSoOrderStatus($orderStatus),
            'buyer_email' => $order['OrderId']."@lazada-api.com",
            'currency' => $this->storeCurrency,
            'shipping_address_id' => $addressId
        ];
        if (isset($order['Price'])) {
            $object['total_amount'] = $order['Price'];
        }
        if (isset($order['OrderTotal']['CurrencyCode'])) {
            $object['currency'] = "US";
        }
        if (isset($order['PaymentMethod'])){
            $object['payment_method'] = $order['PaymentMethod'];
        }
        if (isset($order['CustomerFirstName'])) {
            $object['buyer_name'] = $order['CustomerFirstName'];
        }
		if (isset($order['CustomerLastName'])) {
            $object['buyer_name'] .=" ".$order['CustomerLastName'];
        }
        if (isset($order['PromisedShippingTime'])){
            $object['latest_ship_date'] = $order['PromisedShippingTime'];
        }
        if (isset($order['Remarks'])){
            $object['remarks'] = $order['Remarks'];
        }
        $platformMarketOrder = PlatformMarketOrder::updateOrCreate(
            [   'platform_order_id' => $order['OrderId'], 
                'platform' => $storeName
            ],
                $object
        );
        if($platformMarketOrder->acknowledge === "1"){
            $this->markSplitOrderShipped($platformMarketOrder);
        }
        return $platformMarketOrder;
	}

	public function updateOrCreatePlatformMarketOrderItem($platformMarketOrderId, $order, $orderItem, $storeName)
	{
		$object = [
            'platform_market_order_id' => $platformMarketOrderId,
	        'platform_order_id' => $order['OrderId'],
	        'seller_sku' => $orderItem['Sku'],
	        'order_item_id' => $orderItem['OrderItemId'],
	        'title' => $orderItem['Name'],
	        'quantity_ordered' => 1
	    ];
	    if (isset($orderItem['QuantityShipped'])) {
	        $object['quantity_shipped'] = $orderItem['QuantityShipped'];
	    }
	    if (isset($orderItem['ItemPrice'])) {
	        $object['item_price'] = $orderItem['ItemPrice'];
	    }
	    if (isset($orderItem['ShippingAmount'])) {
	        $object['shipping_price'] = $orderItem['ShippingAmount'];
	    }
	    if (isset($orderItem['ItemTax'])) {
	        $object['item_tax'] = $orderItem['TaxAmount'];
	    }
	    //need update
	    if (isset($orderItem['ShippingServiceCost'])) {
	        $object['shipping_tax'] = $orderItem['ShippingServiceCost'];
	    }
	    if (isset($orderItem['VoucherAmount'])) {
	        $object['promotion_discount'] = $orderItem['VoucherAmount'];
	    }
	    if (isset($orderItem['Status'])) {
	        $object['status'] = studly_case($orderItem['Status']);
	    }
	    if (isset($orderItem['ShippingProviderType'])) {
	        $object['ship_service_level'] = $orderItem['ShippingProviderType'];
	    }
	    if (isset($orderItem['ShipmentProvider'])) {
	        $object['shipment_provider'] = $orderItem['ShipmentProvider'];
	    }
	    if (isset($orderItem['TrackingCode'])) {
	        $object['tracking_code'] = $orderItem['TrackingCode'];
	    }
	    if (isset($orderItem['Reason'])) {
	        $object['reason'] = $orderItem['Reason'];
	    }
	    if (isset($orderItem['ReasonDetail'])) {
	        $object['reason_detail'] = $orderItem['ReasonDetail'];
	    }
	    if (isset($orderItem['PackageId'])) {
	        $object['package_id'] = $orderItem['PackageId'];
	    }
	    $platformMarketOrderItem = PlatformMarketOrderItem::updateOrCreate(
	        [
	            'platform_order_id' => $order['OrderId'],
	            'order_item_id' => $orderItem['OrderItemId'],
                'platform' => $storeName
	        ],
	        $object
	    );
	}

	public function updateOrCreatePlatformMarketShippingAddress($order,$storeName)
	{
		$object=array();
		$object['platform_order_id']=$order['OrderId'];
        $object['name'] = $order['AddressShipping']['FirstName']." ".$order['AddressShipping']['LastName'];
        $object['address_line_1'] = $order['AddressShipping']['Address1'];
        $object['address_line_2'] = $order['AddressShipping']['Address2'];
        $object['address_line_3'] = $order['AddressShipping']['Address3']."-".$order['AddressShipping']['Address4']."-".$order['AddressShipping']['Address5'];
        $object['city'] = $order['AddressShipping']['Address3'];
        $object['county'] = $order['AddressShipping']['Country'];
        $object['country_code'] = strtoupper(substr($storeName, -2));
        $object['district'] = '';
        $object['state_or_region'] = '';
        $object['postal_code'] = $order['AddressShipping']['PostCode'];
        $object['phone'] = $order['AddressShipping']['Phone'];

        $object['bill_name'] = $order['AddressBilling']['FirstName']." ".$order['AddressBilling']['LastName'];
        $object['bill_address_line_1'] = $order['AddressBilling']['Address1'];
        $object['bill_address_line_2'] = $order['AddressBilling']['Address2'];
        $object['bill_address_line_3'] = $order['AddressBilling']['Address3']."-".$order['AddressBilling']['Address4']."-".$order['AddressBilling']['Address5'];
        $object['bill_city'] = $order['AddressBilling']['Address3'];
        $object['bill_county'] = $order['AddressBilling']['Country'];
        $object['bill_country_code'] = strtoupper(substr($storeName, -2));
        $object['bill_district'] = '';
        $object['bill_state_or_region'] = '';
        $object['bill_postal_code'] = $order['AddressBilling']['PostCode'];
        $object['bill_phone'] = $order['AddressBilling']['Phone'];

        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(
            [   
                'platform_order_id' => $order['OrderId'],
                'platform' => $storeName,
            ],
            $object
        );
        return $platformMarketShippingAddress->id;
	}


	private function checkResultData($result)
	{
		if($result){
			$this->saveDataToFile(serialize($result),"setStatusToCanceled");
            $result = array("status" => "success");
		}else{
            $result["status"] = "failed";
			$result["message"] = $this->lazadaOrderStatus->errorMessage();
			$result["code"] = $this->lazadaOrderStatus->errorCode();
		}
        return $result;
	}

	public function getSoOrderStatus($platformOrderStatus)
	{
        if(strstr($platformOrderStatus,'||')){
            return PlatformMarketConstService::ORDER_STATUS_UNCONFIRMED;
        }
		switch ($platformOrderStatus) {
			case 'Canceled':
				$status = PlatformMarketConstService::ORDER_STATUS_CANCEL;
				break;
			case 'Shipped':
				$status = PlatformMarketConstService::ORDER_STATUS_SHIPPED;
				break;
            case 'ReadyToShip':
                $status = PlatformMarketConstService::ORDER_STATUS_READYTOSHIP;
                break;
			case 'Unshipped':
			case 'Pending':
			case 'Processing':
				$status = PlatformMarketConstService::ORDER_STATUS_UNSHIPPED;
				break;
			case 'Delivered':
				$status = PlatformMarketConstService::ORDER_STATUS_DELIVERED;
				break;
			case 'Failed':
				$status = PlatformMarketConstService::ORDER_STATUS_FAIL;
				break;
            case 'Returned':
                $status = PlatformMarketConstService::ORDER_STATUS_RETURENED;
                break;
			default:
				return null;
		}
		return $status;
	}

    public function getMettelShipmentProvider($storeName)
    {
        $shipmentProvider = array(
            "MY" => "SkyNet - DS",
            "SG" => "Speedpost",
            "TH" => "Kerry",
            "ID" => "LEX MP",
            "PH" => "LEX",
            "VN" => "LEX"
            );
        $lazadaShipments = $this->getShipmentProviders($storeName);
        $countryCode = strtoupper(substr($storeName, -2));
        if(isset($shipmentProvider[$countryCode])){
           foreach ($lazadaShipments as $lazadaShipment) {
               if($shipmentProvider[$countryCode] == $lazadaShipment["Name"]){
                    return $shipmentProvider[$countryCode];
               }
           }
        }else{
            return null;
        }
    }

    public function getEsgShippingProvider($warehouseId,$countryCode,$lazadaShipments)
    {
        switch ($warehouseId){
            case 'ES_HK':
                $shipmentProvider = array(
                    "MY" => "AS-Poslaju-HK",
                    "SG" => "LGS-SG3-HK",
                    "TH" => "LGS-TH3-HK",
                    "ID" => "LGS-LEX-ID-HK",
                    "PH" => "AS-LBC-JZ-HK Sellers-LZ2"
                    );
                break;
            case '4PX_B66':
            case '4PXDG_PL':
            $shipmentProvider = array(
                    "MY" => "AS-Poslaju",
                    "SG" => "LGS-SG3",
                    "TH" => "LGS-TH1",
                    "ID" => "LGS-Tiki-ID",
                    "PH" => "LGS-PH1"
                    );
                break;
            case 'ES_DGME':
                $shipmentProvider = array(
                    "MY" => "AS-Poslaju",
                    "SG" => "LGS-SG3",
                    "TH" => "LGS-TH3",
                    "ID" => "LGS-Tiki-ID",
                    "PH" => "LGS-PH1"
                    );
                break;
        }
        if(empty($lazadaShipments)){
            return $shipmentProvider[$countryCode];
        }else{
           if($shipmentProvider && isset($shipmentProvider[$countryCode])){
               foreach ($lazadaShipments as $lazadaShipment) {
                   if($shipmentProvider[$countryCode] == $lazadaShipment["Name"]){
                        return $shipmentProvider[$countryCode];
                   }
               }
            }else{
                return null;
            } 
        }
    }

    public function alertSetOrderReadyToShip($storeName)
    {
        $pendingOrderList = $this->getPendingOrderList($storeName);
        $orderId = null;
        if($pendingOrderList){
            foreach($pendingOrderList as $pendingOrder){
                $expierDate = strtotime("+2 days",strtotime($pendingOrder["CreatedAt"]));
                $currentDate = strtotime(date("Y-m-d 23:59:59"));
                if($expierDate - $currentDate <= 0)  {
                    $orderId[] = $pendingOrder['OrderNumber'];
                }
            }
            return $orderId;
        }
    }

    public function sendAlertMailMessage($storeName,$esgOrders)
    {
        $subject = "MarketPlace: [{$storeName}] Order Ready To Ship Alert!\r\n";
        $message = "These order will be late for ready to ship. Please act now!\r\n";
        foreach($esgOrders as $esgOrder){
            $message .="ESG Order No ".$esgOrder->so_no." (Platform Order No ".$esgOrder->platform_order_id.") status is ".$esgOrder->status.".\r\n";
        }
        $message .= "Thanks\r\n";
        $this->sendMailMessage('storemanager@brandsconnect.net,fiona@etradegroup.net', $subject, $message);
        return false;
    }

    private function getManifestCss()
    {
        $css = '<style type="text/css">img{max-width:960px;margin-bottom: 15px;}
                table {width: 100%;border-collapse: collapse;margin: 15px 0;
                }table th, table td {padding: 5px 10px;text-align: left;vertical-align: top;font-size: 16px;font-family: Arial, Helvetica, sans-serif;line-height: 30px;
                }table th {font-weight: bold;border-top: 3px solid #CCCCCC;border-bottom: 3px solid #CCCCCC;}
                table td {border-bottom: 2px solid #CCCCCC;}</style>';
        return $css;
    }

    public function getProductMainImage($storeName,$sellerSkuList)
    {
        $productMainImage = null;
        $this->lazadaProductList = new LazadaProductList($storeName);
        $this->lazadaProductList->setSkuSellerList($sellerSkuList);
        $productList = $this->lazadaProductList->fetchProductList();
        if($productList){
            foreach ($productList as $product) {
                if(isset($product["SellerSku"])){
                    $productMainImage[$product["SellerSku"]] = $product["MainImage"];
                }else if(isset($product["Skus"]['Sku']['SellerSku'])){
                    $productMainImage[$product["Skus"]['Sku']["SellerSku"]] = $product["Skus"]['Sku']["Images"]["Image"]['0'];
                }
            }
        }
        return $productMainImage;
    }

    public function getFormatDocumentFile($documentFile,$doucmentType,$mattelDcSkuList = array())
    {
        if($doucmentType == "shippingLabel"){
            $documentFile = preg_replace(array('/-445px/'), array('-435px'), $documentFile);
            $documentFile = "<style>body { padding-top: 10px;}</style>".$documentFile;
        }
        if($doucmentType == "invoice"){
            $documentFile = preg_replace(array('/<th>Seller SKU<\/th>/'), array('<th>Seller SKU</th><th>DC SKU</th>'), $documentFile);
            foreach($mattelDcSkuList as $mattelDcSkuArr){
                foreach ($mattelDcSkuArr as $marketplaceSku => $mattelDcSku) {
                   $pattern = array('/<td>'.$marketplaceSku.'<\/td>/');
                   $replacement = array('<td>'.$marketplaceSku.'</td><td>'.$mattelDcSku.'</td>');
                   $documentFile = preg_replace($pattern, $replacement, $documentFile);
                }
            }
        }
        return $documentFile;
    }

    private function markSplitOrderShipped($order)
    {
        $splitOrders = So::where('platform_order_id', '=', $order->platform_order_no)
            ->where('platform_split_order', '=', 1)
            ->where('status', '!=', '6')
            ->get();
        $groupOrders = So::where('platform_order_id', '=', $order->platform_order_no)
            ->where('platform_group_order', '=', 1)
            ->where('status', '=', '6')
            ->get();
        if(!$splitOrders->isEmpty() && !$groupOrders->isEmpty()){
            $splitOrders->map(function ($splitOrder) use ($order) {
                $splitOrder->dispatch_date = $order->dispatch_date;
                $splitOrder->status = 6;
                $splitOrder->save();
            });
        }

    }
    private function checkPlatformMarketUnshippedOrder($platformMarketOrder)
    {
        $order = $this->getOrder($platformMarketOrder->platform,$platformMarketOrder->platform_order_id);
        if($order["Statuses"]){
            if(is_array($order['Statuses']["Status"])){
                $orderStatus=implode("||",$order['Statuses']["Status"]);
            }else{
                $orderStatus=studly_case($order['Statuses']["Status"]);
            }
            if($platformMarketOrder->order_status == $orderStatus){
                return true;
            }else{
                $platformMarketOrder->order_status = $orderStatus;
                $platformMarketOrder->esg_order_status = $this->getSoOrderStatus($orderStatus);
                $platformMarketOrder->save();
                return false;
            }
        }
    }

}