<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Schedule;

//use newegg api package
use App\Repository\NeweggMws\NeweggOrder;
use App\Repository\NeweggMws\NeweggOrderList;
use App\Repository\NeweggMws\NeweggOrderItemList;
use App\Repository\NeweggMws\NeweggOrderStatus;
use App\Repository\NeweggMws\NeweggDocument;
use App\Repository\NeweggMws\NeweggShipmentProviders;
use App\Repository\NeweggMws\NeweggMultipleOrderItems;

class ApiNeweggService extends ApiBaseService  implements ApiPlatformInterface
{
	private $storeCurrency;
	function __construct()
	{

	}

	public function getPlatformId()
	{
		return "Newegg";
	}

	public function retrieveOrder($storeName)
	{
		$originOrderList=$this->getOrderList($storeName);
    	$orderInfoList = $originOrderList["OrderInfoList"]["OrderInfo"];
        if($orderInfoList){
        	foreach($orderInfoList as $order){
				if (isset($order['ShipToCountryCode'])) {
					$addressId=$this->updateOrCreatePlatformMarketShippingAddress($order,$storeName);
				}
				$this->updateOrCreatePlatformMarketOrder($order,$addressId,$storeName);
				$originOrderItemList=$this->getOrderItemList($storeName,$order["OrderId"]);
				if($originOrderItemList){
					foreach($originOrderItemList as $orderItem){
						$this->updateOrCreatePlatformMarketOrderItem($order,$orderItem);
					}
				}
			}
			return true;
        }
	}

	public function getOrder($storeName,$orderId)
	{
		$this->neweggOrder=new NeweggOrder($storeName);
		// $this->storeCurrency=$this->neweggOrder->getStoreCurrency();
		$this->neweggOrder->setOrderId($orderId);
		$returnData=$this->neweggOrder->fetchOrder();
		return $returnData;
	}

	public function getOrderList($storeName)
	{
		$this->neweggOrderList=new NeweggOrderList($storeName);
		// $this->storeCurrency=$this->neweggOrderList->getStoreCurrency();
		$lastAccessTime = $this->getSchedule()->last_access_time;
		$pstTimezone = new \DateTimeZone("PST");
		$dt = new \DateTime($lastAccessTime, $pstTimezone);
		$dateTime = $dt->format("Y-m-d");

		$this->neweggOrderList->setOrderDateFrom($dateTime);
		$originOrderList=$this->neweggOrderList->fetchOrderList();
		$this->saveDataToFile(serialize($originOrderList),"getOrderList");
        return $originOrderList;
	}

	public function getOrderItemList($storeName,$orderId)
	{
		$this->neweggOrderItemList = new NeweggOrderItemList($storeName);
		$this->neweggOrderItemList->setOrderId($orderId);
		$orginOrderItemList=$this->neweggOrderItemList->fetchOrderItemList();
		$this->saveDataToFile(serialize($orginOrderItemList),"getOrderItemList");
        return $orginOrderItemList;
	}

	public function submitOrderFufillment($esgOrder,$esgOrderShipment,$platformOrderIdList)
	{  
        return false;//testing
		$storeName = $platformOrderIdList[$esgOrder->platform_order_id];
		$orderItemIds = array();
		$extItemCd = $esgOrder->soItem->pluck("ext_item_cd");
        foreach($extItemCd as $extItem){
            $itemIds = explode("||",$extItem);
            foreach($itemIds as $itemId){
                $orderItemIds[] = $itemId;
            }
        }
        //$shipmentProviders = $this->getShipmentProviders($storeName);
        $countryCode = strtoupper(substr($storeName, -2));
        $shipmentProvider = $this->getEsgShippingProvider($countryCode);
        if ($esgOrderShipment) {
            $marketplacePacked = $this->setStatusToPackedByMarketplace($storeName,$orderItemId,$shipmentProvider);
            if($marketplacePacked){
                //valid orderItem trackingNumber 
                foreach($marketplacePacked as $packed){
                   $shippingObject[$packed["TrackingNumber"]]["OrderItemId"][] = $packed["OrderItemId"];
                   $shippingObject[$packed["TrackingNumber"]]["ShipmentProvider"]= $packed["ShipmentProvider"];
                }
                $this->getDocument($storeName,$orderItems,"invoice");
                foreach($shippingObject as $trackinCode => $itemObject){
                    $itemObject["TrackingNumber"] = $trackinCode;
                    $result = $this->setStatusToReadyToShip($storeName,$itemObject);
                }
    			return $marketplacePacked;
            }else{
                return false;
            }
        }
	}

    public function getShipmentProviders($storeName)
    {
        $this->neweggShipmentProviders=new NeweggShipmentProviders($storeName);
        $result = $this->neweggShipmentProviders->fetchShipmentProviders();
        return $result;
    }

	public function setStatusToCanceled($storeName,$orderItemId)
	{
		$this->neweggOrderStatus=new NeweggOrderStatus($storeName);
		$this->neweggOrderStatus->setOrderItemId($orderItemId);
		$this->neweggOrderStatus->setReason("reason");
		$this->neweggOrderStatus->setReasonDetail("reasonDetail");
		$result=$this->neweggOrderStatus->setStatusToCanceled();
		return $this->checkResultData($result,$this->neweggOrderStatus);
	}

	public function setStatusToPackedByMarketplace($storeName,$orderItemIds,$shipmentProvider)
	{
		$this->neweggOrderStatus = new NeweggOrderStatus($storeName);
		$this->neweggOrderStatus->setOrderItemIds($orderItemIds);
		$this->neweggOrderStatus->setDeliveryType("dropship");
		$this->neweggOrderStatus->setShippingProvider($shipmentProvider);
		$orginOrderItemList=$this->neweggOrderStatus->setStatusToPackedByMarketplace();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToPackedByMarketplace");
        return $orginOrderItemList;
	}

    public function getMultipleOrderItems($storeName,$orderIdList)
    {
        $this->neweggMultipleOrderItems = new NeweggMultipleOrderItems($storeName);
        $this->neweggMultipleOrderItems->setOrderIdList($orderIdList);
        $orginOrderItemList=$this->neweggMultipleOrderItems->fetchMultipleOrderItems();
        $this->saveDataToFile(serialize($orginOrderItemList),"fetchMultipleOrderItems");
        return $orginOrderItemList;
    }

    public function getDocument($storeName,$orderItemIds,$documentType)
    {
        $this->neweggDocument = new NeweggDocument($storeName);
        $this->neweggDocument->setDocumentType($documentType);
        $this->neweggDocument->setOrderItemIds($orderItemIds);
        $document = $this->neweggDocument->fetchDocument();
        print_r($document);exit();
        return $document;
    }

	public function setStatusToReadyToShip($storeName,$itemObject)
	{  
		$this->neweggOrderStatus = new NeweggOrderStatus($storeName);
		$this->neweggOrderStatus->setOrderItemIds($itemObject["OrderItemId"]);
		$this->neweggOrderStatus->setDeliveryType("dropship");
		$this->neweggOrderStatus->setShippingProvider($itemObject["ShipmentProvider"]);
		$this->neweggOrderStatus->setTrackingNumber($itemObject["TrackingNumber"]);
		$orginOrderItemList=$this->neweggOrderStatus->setStatusToReadyToShip();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToReadyToShip");
        return $orginOrderItemList;
	}

	public function setStatusToShipped($storeName,$orderId)
	{
		$this->neweggOrderStatus = new NeweggOrderStatus($storeName);
		$this->neweggOrderStatus->setOrderItemId($orderItemId);
		$orginOrderItemList=$this->neweggOrderStatus->setStatusToShipped();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToShipped");
        return $orginOrderItemList;
	}

	public function setStatusToFailedDelivery($storeName,$orderId)
	{
		$this->neweggOrderStatus = new NeweggOrderStatus($storeName);
		$this->neweggOrderStatus->setOrderItemId($orderItemId);
		$this->neweggOrderStatus->setReason("reason");
		$this->neweggOrderStatus->setReasonDetail("reasonDetail");
		$orginOrderItemList=$this->neweggOrderStatus->setStatusToFailedDelivery();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToFailedDelivery");
        return $orginOrderItemList;
	}

	public function setStatusToDelivered($storeName,$orderId)
	{
		$this->neweggOrderStatus = new NeweggOrderStatus($storeName);
		$this->neweggOrderStatus->setOrderItemId($orderItemId);
		$orginOrderItemList=$this->neweggOrderStatus->setStatusToDelivered();
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
		$object = [
            'platform' => $storeName,
            'biz_type' => "Newegg",
            'platform_order_id' => $order['OrderId'],
            'platform_order_no' => $order['OrderNumber'],
            'purchase_date' => $order['CreatedAt'],
            'last_update_date' => $order['UpdatedAt'],
            'order_status' => $orderStatus,
            'esg_order_status'=>$this->getSoOrderStatus($orderStatus),
            'buyer_email' => $order['OrderId']."@newegg-api.com",
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
		$object['platform_order_id']=$order['OrderNumber'];
        $object['name'] = $order['CustomerName'];
        $object['address_line_1'] = $order['ShipToAddress1'];
        $object['address_line_2'] = $order['ShipToAddress2'];
        $object['address_line_3'] = "";
        $object['city'] = $order['ShipToCityName'];
        $object['county'] = $order['ShipToStateCode'];
        $object['country_code'] = strtoupper(substr($storeName, -2));
        $object['district'] = '';
        $object['state_or_region'] = $order['ShipToStateCode'];
        $object['postal_code'] = $order['ShipToZipCode'];
        $object['phone'] = $order['CustomerPhoneNumber'];

// ping stop here
        $object['bill_name'] = $order['AddressBilling']['FirstName']." ".$order['AddressBilling']['LastName'];
        $object['bill_address_line_1'] = $order['AddressBilling']['Address1'];
        $object['bill_address_line_2'] = $order['AddressBilling']['Address2'];
        $object['bill_address_line_3'] = $order['AddressBilling']['Address3']."-".$order['AddressBilling']['Address4']."-".$order['AddressBilling']['Address5'];
        $object['bill_city'] = $order['AddressBilling']['Address3'];
        $object['bill_county'] = $order['AddressBilling']['Country'];
        $object['bill_country_code'] = strtoupper(substr($storeName, -2));
        $object['bill_district'] = $order['AddressBilling']['Ward'];
        $object['bill_state_or_region'] = $order['AddressBilling']['Region'];
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
			return true;
		}else{
			$error["message"]=$this->neweggOrderStatus->errorMessage();
			$error["code"]=$this->neweggOrderStatus->errorCode();
			return $error;
		}
	}

	public function getSoOrderStatus($platformOrderStatus)
	{
		switch ($platformOrderStatus) {
			case 'Canceled':
				$status=PlatformOrderService::ORDER_STATUS_CANCEL;
				break;
			case 'Shipped':
				$status=PlatformOrderService::ORDER_STATUS_SHIPPED;
				break;
            case 'ReadyToShip':
			case 'Unshipped':
			case 'Pending':
			case 'Processing':
				$status=PlatformOrderService::ORDER_STATUS_UNSHIPPED;
				break;
			case 'Delivered':
				$status=PlatformOrderService::ORDER_STATUS_DELIVERED;
				break;
			case 'Failed':
				$status=PlatformOrderService::ORDER_STATUS_FAIL;
				break;
			default:
				return null;
		}
		return $status;
	}

    /*
        $marketplacePacked = $this->setStatusToPackedByMarketplace($storeName,$orderItemId);
        if($marketplacePacked)
        $orderItems = $this->getMultipleOrderItems($storeName,$esgOrder->platform_order_id);
        foreach($orderItems as $orderItem){
            $shippingObject[$orderItem["TrackingCode"]][] = array(
                    ["OrderItemId"] = $orderItem["OrderItemId"],
                    ["ShippingProviderType"] = $orderItem["ShippingProviderType"],
                    ["PurchaseOrderNumber"] = $orderItem["PurchaseOrderNumber"],
                );
        }
    */

    public function getEsgShippingProvider($countryCode)
    {
        $shipmentProvider = array(
            "MY" => "AS-Poslaju-HK",      
            "SG" => "LGS-SG3-HK",                
            "TH" => "LGS-TH3-HK",       
            "ID" => "LGS-LEX-ID-HK",
            "PH" => "AS-LBC-JZ-HK Sellers-LZ2"
        );
        if(isset($shipmentProvider[$countryCode]))
        return $shipmentProvider[$countryCode];
    }

}