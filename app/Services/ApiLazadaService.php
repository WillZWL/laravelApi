<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Schedule;
use PDF;

//use lazada api package
use App\Repository\LazadaMws\LazadaOrder;
use App\Repository\LazadaMws\LazadaOrderList;
use App\Repository\LazadaMws\LazadaOrderItemList;
use App\Repository\LazadaMws\LazadaOrderStatus;
use App\Repository\LazadaMws\LazadaDocument;
use App\Repository\LazadaMws\LazadaShipmentProviders;
use App\Repository\LazadaMws\LazadaMultipleOrderItems;

class ApiLazadaService extends ApiBaseService  implements ApiPlatformInterface
{
	private $storeCurrency;
	function __construct()
	{

	}

	public function getPlatformId()
	{
		return "Lazada";
	}

	public function retrieveOrder($storeName)
	{
		$originOrderList=$this->getOrderList($storeName);
        if($originOrderList){
        	foreach($originOrderList as $order){
				if (isset($order['AddressShipping'])) {
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
		$this->lazadaOrder=new LazadaOrder($storeName);
		$this->storeCurrency=$this->lazadaOrder->getStoreCurrency();
		$this->lazadaOrder->setOrderId($orderId);
		$returnData=$this->lazadaOrder->fetchOrder();
		return $returnData;
	}

	public function getOrderList($storeName)
	{
		$this->lazadaOrderList=new LazadaOrderList($storeName);
		$this->storeCurrency=$this->lazadaOrderList->getStoreCurrency();
		$dateTime=date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
		$this->lazadaOrderList->setUpdatedAfter($dateTime);
		$originOrderList=$this->lazadaOrderList->fetchOrderList();
		$this->saveDataToFile(serialize($originOrderList),"getOrderList");
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
        $this->lazadaShipmentProviders=new LazadaShipmentProviders($storeName);
        $result = $this->lazadaShipmentProviders->fetchShipmentProviders();
        return $result;
    }

	public function setStatusToCanceled($storeName,$orderItemId)
	{
		$this->lazadaOrderStatus=new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemId($orderItemId);
		$this->lazadaOrderStatus->setReason("reason");
		$this->lazadaOrderStatus->setReasonDetail("reasonDetail");
		$result=$this->lazadaOrderStatus->setStatusToCanceled();
		return $this->checkResultData($result,$this->lazadaOrderStatus);
	}

	public function setStatusToPackedByMarketplace($storeName,$orderItemIds,$shipmentProvider)
	{
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemIds($orderItemIds);
		$this->lazadaOrderStatus->setDeliveryType("dropship");
		$this->lazadaOrderStatus->setShippingProvider($shipmentProvider);
		$orginOrderItemList=$this->lazadaOrderStatus->setStatusToPackedByMarketplace();
		$this->saveDataToFile(serialize($orginOrderItemList),"setStatusToPackedByMarketplace");
        return $orginOrderItemList;
	}

    public function getMultipleOrderItems($storeName,$orderIdList)
    {
        $this->lazadaMultipleOrderItems = new LazadaMultipleOrderItems($storeName);
        $this->lazadaMultipleOrderItems->setOrderIdList($orderIdList);
        $orginOrderItemList=$this->lazadaMultipleOrderItems->fetchMultipleOrderItems();
        $this->saveDataToFile(serialize($orginOrderItemList),"fetchMultipleOrderItems");
        return $orginOrderItemList;
    }

    public function getDocument($storeName,$orderItemIds,$documentType)
    {
        $this->lazadaDocument = new LazadaDocument($storeName);
        $this->lazadaDocument->setDocumentType($documentType);
        $this->lazadaDocument->setOrderItemIds($orderItemIds);
        $document = $this->lazadaDocument->fetchDocument();
        if(isset($File)){
            $fileData = base64_decode($document["File"]);
            $filename =$this->getPlatformId().'/'.$documentType."/".$orderItemIds;  
            $pdfFile= \Storage::disk('pdf')->getDriver()->getAdapter()->getPathPrefix().$filename.".pdf";
            PDF::loadHTML($fileData)->setPaper('a4')->setOrientation('landscape')->setOption('margin-bottom', 0)->save($pdfFile);
            return $pdfFile;
        }
    }

	public function setStatusToReadyToShip($storeName,$itemObject)
	{  
		$this->lazadaOrderStatus = new LazadaOrderStatus($storeName);
		$this->lazadaOrderStatus->setOrderItemIds($itemObject["OrderItemId"]);
		$this->lazadaOrderStatus->setDeliveryType("dropship");
		$this->lazadaOrderStatus->setShippingProvider($itemObject["ShipmentProvider"]);
		$this->lazadaOrderStatus->setTrackingNumber($itemObject["TrackingNumber"]);
		$orginOrderItemList=$this->lazadaOrderStatus->setStatusToReadyToShip();
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
		$object = [
            'platform' => $storeName,
            'biz_type' => "Lazada",
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
        $object['district'] = $order['AddressShipping']['Ward'];
        $object['state_or_region'] = $order['AddressShipping']['Region'];
        $object['postal_code'] = $order['AddressShipping']['PostCode'];
        $object['phone'] = $order['AddressShipping']['Phone'];

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
			$error["message"]=$this->lazadaOrderStatus->errorMessage();
			$error["code"]=$this->lazadaOrderStatus->errorCode();
			return $error;
		}
	}

	public function getSoOrderStatus($platformOrderStatus)
	{
		switch ($platformOrderStatus) {
			case 'Canceled':
				$status=PlatformMarketConstService::ORDER_STATUS_CANCEL;
				break;
			case 'Shipped':
				$status=PlatformMarketConstService::ORDER_STATUS_SHIPPED;
				break;
            case 'ReadyToShip':
			case 'Unshipped':
			case 'Pending':
			case 'Processing':
				$status=PlatformMarketConstService::ORDER_STATUS_UNSHIPPED;
				break;
			case 'Delivered':
				$status=PlatformMarketConstService::ORDER_STATUS_DELIVERED;
				break;
			case 'Failed':
				$status=PlatformMarketConstService::ORDER_STATUS_FAIL;
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