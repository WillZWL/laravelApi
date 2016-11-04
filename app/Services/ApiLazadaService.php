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
use App\Models\PlatformMarketReasons;
use PDF;
use Excel;
use Zipper;

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
    					$addressId=$this->updateOrCreatePlatformMarketShippingAddress($order,$storeName);
    				}
				    $this->updateOrCreatePlatformMarketOrder($order,$addressId,$storeName);
					foreach($order["OrderItems"] as $orderItems){
						if(isset($orderItems["OrderItemId"])){
                            $this->updateOrCreatePlatformMarketOrderItem($order,$orderItems);
                        }else{
                            foreach ($orderItems as $orderItem) {
                                $this->updateOrCreatePlatformMarketOrderItem($order,$orderItem);
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
            foreach ($originOrderList as $order) {
                $orderIdList[] = $order['OrderId'];
                $newOrderList[$order['OrderId']] = $order;
            }
            $multipleOrderItems = $this->getMultipleOrderItems($storeName,$orderIdList);
            if($multipleOrderItems){
                foreach ($multipleOrderItems as $orderItems) {
                $newOrderList[$orderItems["OrderId"]]["OrderItems"] = $orderItems["OrderItems"];
                }
                return $newOrderList;
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

    //ESG SYSTEM SET ORDER TO READYSHIP AND GET DOCUMENT
    public function esgOrderReadyToShip($soNoList)
    {  
        $pdfFilePath = "/var/data/shop.eservicesgroup.com/marketplace/".date("Y")."/".date("m")."/".date("d")."/lazada/label/";
        $result = "";$returnData = "";
        $esgOrders = So::whereIn('so_no', $soNoList)
                ->where("biz_type","like","%Lazada")
                ->get();
        if(!$esgOrders->isEmpty()) {
            $esgOrderGroups = $esgOrders->groupBy("platform_id");
            $returnData = $this->esgOrderApiReadyToShip($esgOrderGroups,$pdfFilePath);
            if(isset($returnData["documentLabel"])){
                $returnData["document"] = $this->getESGOrderDocumentLabel($returnData["documentLabel"],$pdfFilePath);
            }
            return $result = array("status" => "success","message" => $returnData); 
        }else{
            return $result = array("status" => "failed","message" => "Invalid Order");
        }
    }

    public function orderFufillmentReadyToShip($orderGroup,$warehouse)
    {   
        $returnData = array();$warehouseInventory = null;
        foreach($orderGroup as $order){
            if($order->esg_order_status != 6){
                if(isset($warehouseInventory["warehouse"])){
                    $warehouse = $warehouseInventory["warehouse"]; 
                }
                //$warehouseInventory = $this->checkWarehouseInventory($order,$warehouse);
                //if($warehouseInventory["updateObject"]){
                    $orderItemIds = array();
                    foreach($order->platformMarketOrderItem as $orderItem){
                        $orderItemIds[] = $orderItem->order_item_id;
                    }
                    $shipmentProvider = $this->getMettelShipmentProvider($order->platform);
                    if($shipmentProvider && $orderItemIds){
                        $result = $this->setApiOrderReadyToShip($order->platform,$orderItemIds,$shipmentProvider,$order->platform_order_id);
                        if($result){
                            $orderIdList[] = $order->platform_order_id;
                            $this->updateOrderStatusReadyToShip($order->platform,$orderIdList);
                            //$this->updateWarehouseInventory($order->so_no,$warehouseInventory["updateObject"]);
                            //$this->updatePlatformMarketInventory($order,$warehouseInventory["updateObject"]);
                            $returnData[$order->platform_order_no] = " Set Ready To Ship Success\r\n";
                        }else{
                            $returnData[$order->platform_order_no] = " Set Ready To Ship Failed\r\n";
                        }
                    }else{
                        $returnData[$order->platform_order_no] = "Shipment Provider is not exit in lazada admin system.";
                    }
               // }
            }
        }
        return $returnData;
    }

    public function merchantOrderFufillmentGetDocument($orderGroups,$doucmentType)
    {
        $orderItemIds = array();$fileDate = date("h-i-s");
        $filePath = \Storage::disk('merchant')->getDriver()->getAdapter()->getPathPrefix();
        $pdfFilePath = $filePath.date("Y")."/".date("m")."/".date("d")."/label/";
        $doucmentFile = "";
        foreach($orderGroups as $storeName => $orderGroup){
            foreach($orderGroup as $order){
                $orderItem = $order->platformMarketOrderItem->first();
                $orderItemIds[] = $orderItem->order_item_id; 
            }
            $doucmentFile .= $this->getDocument($storeName,$orderItemIds,$doucmentType);
        }
        if($doucmentFile){
            if($doucmentType == "shippingLabel"){ 
                $doucmentFile = preg_replace(array('/-445px/'), array('-435px'), $doucmentFile);
                $doucmentFile = "<style>body { padding-top: 10px;}</style>".$doucmentFile;
            }
            $file = $doucmentType.$fileDate.'.pdf';
            PDF::loadHTML($doucmentFile)->setOption('margin-top', 5)->setOption('margin-bottom', 5)->save($pdfFilePath.$file);
            $pdfFile = url("api/merchant-api/download-label/".$file);
            return $pdfFile;
        }  
    }

    //run request to lazada api set order ready to ship one by one
    private function esgOrderApiReadyToShip($esgOrderGroups,$pdfFilePath)
    {
        $returnData = null;
        foreach($esgOrderGroups as $platformId => $esgOrderGroup)
        {
            $ordersIdList = null; $doucumentOrderItemIds = null;
            $prefix = strtoupper(substr($platformId,3,2));
            $countryCode = strtoupper(substr($platformId, -2));
            $storeName = $prefix."LAZADA".$countryCode;
            $lazadaShipments = $this->getShipmentProviders($storeName);
            foreach($esgOrderGroup as $esgOrder)
            {   
                if(!$esgOrder->soAllocate->isEmpty() && $esgOrder->status != 6){
                    $warehouseId = $esgOrder->soAllocate->first()->warehouse_id;
                    $orderItemIds = $this->checkEsgOrderIventory($warehouseId,$esgOrder);
                    if($orderItemIds){
                        $updateWarehouseObject[] = $esgOrder;
                        $shipmentProvider = $this->getEsgShippingProvider($warehouseId,$countryCode,$lazadaShipments);
                        if($shipmentProvider){
                            $result = $this->setApiOrderReadyToShip($storeName,$orderItemIds,$shipmentProvider,$esgOrder->txn_id);
                            if($result){
                                $returnData[$esgOrder->so_no] = " Set Ready To Ship Success\r\n";
                                $doucumentOrderItemIds[] = $orderItemIds[0];
                                $ordersIdList[] = $esgOrder->txn_id;
                            }else{
                                $returnData[$esgOrder->so_no] = " Set Ready To Ship Failed\r\n";
                            }
                        }else{
                            $returnData[$esgOrder->so_no] = "Shipment Provider is not exit in lazada admin system.";
                        }
                    }
                }
            }
            if($doucumentOrderItemIds){
                $returnData["documentLabel"][$storeName] = $doucumentOrderItemIds;
            }
            if($ordersIdList){
                $this->updateOrderStatusReadyToShip($storeName,$ordersIdList);
                $this->updateEsgWarehouseInventory($updateWarehouseObject);
            }
        }
        return $returnData;   
    }

    private function getESGOrderDocumentLabel($documentLabels,$pdfFilePath)
    {
        $document = array();
        //$style ='<style type="text/css">.page {overflow: hidden;page-break-inside: avoid;}}</style>';
        //
        $pdfHrDom ='<hr style="page-break-after: always;border-top: 3px dashed;">';
        $doucmentTypeArr = ["invoice","carrierManifest","shippingLabel"];
        foreach($doucmentTypeArr as $doucmentType ){
            foreach ($documentLabels as $storeName => $orderItemId) {
                $fileHtml = $this->getDocument($storeName,$orderItemId,$doucmentType);
                if($fileHtml){
                    if($doucmentType == "invoice"){
                    $fileHtml = preg_replace(array('/class="logo"/'), array('class="page"'), $fileHtml,2);
                    }
                    if(isset($document[$doucmentType])){
                        $document[$doucmentType] .= $pdfHrDom.$fileHtml;
                    }else{
                        $document[$doucmentType] = $fileHtml;
                    }
                }
            }
        }
        if($document){
           return $this->getDocumentSaveToDirectory($document,$pdfFilePath);
        }
        return null;
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

    private function checkEsgOrderIventory($warehouseId,$esgOrder)
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

    private function setApiOrderReadyToShip($storeName,$orderItemIds,$shipmentProvider,$orderId)
    {
        $responseResult = null;
        if ($orderItemIds) {
            $itemObject = array("orderItemIds" => $orderItemIds);
            $marketplacePacked = $this->setStatusToPackedByMarketplace($storeName,$orderItemIds,$shipmentProvider);
            $countryCode = strtoupper(substr($storeName, -2));
            if($countryCode == "TH"){
                $orderList = $this->getMultipleOrderItems($storeName,[$orderId]);
                //Not allowed to change the preselected shipment provider
                foreach ($orderList as $order) {
                    foreach ($order["OrderItems"] as $orderItem) {
                        if($orderItem["TrackingCode"]){
                           $itemObject["TrackingNumber"] = $orderItem["TrackingCode"];
                           $itemObject["ShipmentProvider"] = $shipmentProvider;
                        }
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
                    $object = array(
                        'platform_order_id' => $order["OrderId"],
                        'order_item_id' => $orderItem["OrderItemId"],
                        'shipment_provider' => $orderItem["ShipmentProvider"],
                        'tracking_code' => $orderItem["TrackingCode"],
                        'status' => $orderItem["Status"],
                    );
                    PlatformMarketOrderItem::where("platform_order_id",$order['OrderId'])
                                    ->where('order_item_id',$orderItem['OrderItemId'])
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

    private function getDocumentSaveToDirectory($document,$pdfFilePath)
    {
        $documentPdf = array();
        $fileDate = date("h-i-s");
        if (!file_exists($pdfFilePath)) {
            mkdir($pdfFilePath, 0755, true);
        } 
        foreach($document as $documentType => $documentFile){
            if($documentFile){
                $file = $pdfFilePath.$documentType.$fileDate.'.pdf';
                PDF::loadHTML($documentFile)->save($file);
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
        if($orderParam["reason"])
		$this->lazadaOrderStatus->setReason($orderParam["reason"]);
        if($orderParam["reasonDetail"])
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
            'platform' => $storeName,
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
            ['platform_order_id' => $order['OrderId']],
            $object
        );
        return $platformMarketOrder;
	}

	public function updateOrCreatePlatformMarketOrderItem($order,$orderItem)
	{
		$object = [
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
	            'order_item_id' => $orderItem['OrderItemId']
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

        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(['platform_order_id' => $order['OrderId']],$object
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
            "SG" => "LGS-SG3-HK",                
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
        foreach ($productList as $product) {
           $productMainImage[$product["SellerSku"]] = $product["MainImage"];
        }
        return $productMainImage;
    }

}