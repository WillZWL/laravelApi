<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Schedule;

// Newegg API
use App\Repository\NeweggMws\NeweggOrderItemList;
use App\Repository\NeweggMws\NeweggOrderStatus;
// below not yet set up
use App\Repository\NeweggMws\NeweggOrder;
use App\Repository\NeweggMws\NeweggOrderList;
use App\Repository\NeweggMws\NeweggDocument;
use App\Repository\NeweggMws\NeweggShipmentProviders;
use App\Repository\NeweggMws\NeweggMultipleOrderItems;
use App\Models\CourierInfo;
use App\Models\So;
// test feature 1

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
        $orderInfoList = $originOrderList["OrderInfoList"];

        if($orderInfoList){
            foreach($orderInfoList as $order){
                //check if order has been imported before
                $checkOrder = So::where('platform_order_id', '=', $order['OrderNumber'])->where('biz_type', '=', 'Newegg')->first();

                if(!$checkOrder){
                    if (isset($order['ShipToCountryCode'])) {
                        $addressId=$this->updateOrCreatePlatformMarketShippingAddress($order,$storeName);
                    }
                    $platformMarketOrder = $this->updateOrCreatePlatformMarketOrder($order,$addressId,$storeName);
                    if(isset($order["ItemInfoList"]) && !empty($order["ItemInfoList"])){
                        foreach($order["ItemInfoList"] as $orderItem){
                            $this->updateOrCreatePlatformMarketOrderItem($order,$orderItem);
                        }
                    }                   
                }else{
                        $orderno = $order['OrderNumber'];
                        $subject = "Duplicate Order Alert: MarketPlace: [{$storeName}] Order [{$orderno}]\r\n";
                        $message = "Duplicated Order found for [{$storeName}] Order no [{$orderno}]. \r\n";
                        $message .="Order will not be imported. Please check to ensure this order is the same as what we have\r\n";
                        $message .= "Thanks\r\n";
                        $email = 'jerry.lim@eservicesgroup.com';
                        $this->sendAlertMailMessage($email, $subject, $message);
                        return false;
                }
            }
            return true;
        }
    }

    public function getOrderList($storeName)
    {
        $this->neweggOrderList=new NeweggOrderList($storeName);
        // $this->storeCurrency=$this->neweggOrderList->getStoreCurrency();
        $lastAccessTime = $this->getSchedule()->last_access_time;

        $dateTime = $this->convertFromUtcToPst($lastAccessTime, "Y-m-d");
        $this->neweggOrderList->setOrderDateFrom($dateTime);
        $originOrderList=$this->neweggOrderList->fetchOrderList();
        $this->saveDataToFile(serialize($originOrderList),"getOrderList");
        return $originOrderList;
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order,$addressId,$storeName)
    {
        # Newegg's time is in PST
        $utcOrderDate = $this->convertFromPstToUtc($order['OrderDate'], "m/d/Y H:i:s", "Y-m-d H:i:s");

        $object = [
            'platform' => $storeName,
            'biz_type' => "Newegg",
            'platform_order_id' => $order['OrderNumber'],
            'platform_order_no' => $order['OrderNumber'],
            'purchase_date' => $utcOrderDate,
            'last_update_date' => '0000-00-00 00:00:00',
            'order_status' => studly_case("({$order['OrderStatus']}) {$order["OrderStatusDescription"]}"),
            'esg_order_status'=>$this->getSoOrderStatus($order['OrderStatus']),
            'buyer_email' => $order['CustomerEmailAddress'],
            'currency' => $this->neweggOrderList->getOrderCurrency(),
            'shipping_address_id' => $addressId
        ];

        if (isset($order['OrderTotalAmount'])) {
            $object['total_amount'] = $order['OrderTotalAmount'];
        }

        $object['payment_method'] = '';

        if (isset($order['CustomerName'])) {
            $object['buyer_name'] = $order['CustomerName'];
        }

        if(isset($order["SalesChannel"])) {
            # 0 = Newegg order, 1 = Multi-channel order
            $object["sales_channel"] = $order["SalesChannel"];
        }

        if(isset($order["FulfillmentOption"])) {
            # 0 = Ship by Seller, 1 = Ship by Newegg
            $object["fulfillment_channel"] = $order["FulfillmentOption"];
        }

        if(isset($order["ShipService"])) {
            $object["ship_service_level"] = $order["ShipService"];
        }

        if (isset($order['Memo'])){
            $object['remarks'] = $order['Memo'];
        }

        $platformMarketOrder = PlatformMarketOrder::updateOrCreate(
            ['platform_order_id' => $order['OrderNumber']],
            $object
        );
        return $platformMarketOrder;
    }

    public function updateOrCreatePlatformMarketOrderItem($order,$orderItem)
    {
        $object = [
            'platform_order_id' => $order['OrderNumber'],
            'seller_sku' => $orderItem['SellerPartNumber'],
            'order_item_id' => $orderItem['NeweggItemNumber'],
            'title' => $orderItem['Description'],
            'quantity_ordered' => $orderItem["OrderedQty"]
        ];

        if (isset($orderItem['ShippedQty'])) {
            $object['quantity_shipped'] = $orderItem['ShippedQty'];
        }
        if (isset($orderItem['UnitPrice'])) {
            $object['item_price'] = number_format($orderItem['UnitPrice'] * $orderItem["OrderedQty"], 2, '.', '');            
        }
        if (isset($order['ShippingAmount'])) {
            $object['shipping_price'] = $order['ShippingAmount'];
        }

        $tax = 0;
        if (isset($orderItem['ExtendSalesTax'])) {
            $tax += $orderItem['ExtendSalesTax'];
        }
        if (isset($orderItem['ExtendVAT'])) {
            $tax += $orderItem['ExtendVAT'];
        }
        if (isset($orderItem['ExtendDuty'])) {
            $tax += $orderItem['ExtendDuty'];
        }

        if($tax) {
            $itemTax = number_format($tax/$orderItem["OrderedQty"], 2, '.', '');
        }

        $object['item_tax'] = $tax;
        if(isset($order["ShipService"])) {
            $object["ship_service_level"] = $order["ShipService"];
        }

        if (isset($orderItem['ExtendShippingCharge'])) {
            $object['shipping_price'] = $orderItem['ExtendShippingCharge'];
        }

        if (isset($orderItem['Status'])) {
            $object['status'] = studly_case("({$orderItem['Status']}) {$orderItem["StatusDescription"]}");
        }

        $platformMarketOrderItem = PlatformMarketOrderItem::updateOrCreate(
            [
                'platform_order_id' => $order['OrderNumber'],
                'order_item_id' => $orderItem['NeweggItemNumber']
            ],
            $object
        );
    }

    public function updateOrCreatePlatformMarketShippingAddress($order,$storeName)
    {
        $object=array();
        $object['platform_order_id']=$order['OrderNumber'];
        if(!trim($order['ShipToFirstName']) && !trim($order["ShipToFirstName"]))
            $shipName = $order['CustomerName'];
        else
            $shipName = $order['ShipToFirstName']." ".$order["ShipToFirstName"];

        $object['name'] = $shipName;
        if(trim($order["ShipToCompany"])) {
            $object['address_line_1'] = $order['ShipToCompany'];
            $object['address_line_2'] = $order['ShipToAddress1'];
            $object['address_line_3'] = $order['ShipToAddress2'];
        } else {
            $object['address_line_1'] = $order['ShipToAddress1'];
            $object['address_line_2'] = $order['ShipToAddress2'];
            $object['address_line_3'] = "";
        }

        $object['city'] = $order['ShipToCityName'];
        $object['county'] = $order['ShipToCountryCode'];
        $object['country_code'] = strtoupper(substr($storeName, -2));
        $object['district'] = '';
        $object['state_or_region'] = $order['ShipToStateCode'];
        $object['postal_code'] = $order['ShipToZipCode'];
        $object['phone'] = $order['CustomerPhoneNumber'];

        $object['bill_name'] = $order['CustomerName'];
        $object['bill_address_line_1'] = $order['ShipToAddress1'];
        $object['bill_address_line_2'] = $order['ShipToAddress2'];
        $object['bill_address_line_3'] = "";
        $object['bill_city'] = $order['ShipToCityName'];
        $object['bill_county'] = $order['ShipToCountryCode'];
        $object['bill_country_code'] = strtoupper(substr($storeName, -2));
        $object['bill_district'] = "";
        $object['bill_state_or_region'] = $order['ShipToStateCode'];
        $object['bill_postal_code'] = $order['ShipToZipCode'];
        $object['bill_phone'] = $order['CustomerPhoneNumber'];

        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(['platform_order_id' => $order['OrderNumber']],$object);
        return $platformMarketShippingAddress->id;
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
        switch ($platformOrderStatus) {
            case '4': // voided
                $status=PlatformMarketConstService::ORDER_STATUS_CANCEL;
                break;
            case '2':
                $status=PlatformMarketConstService::ORDER_STATUS_SHIPPED;
                break;
            case '0': // unshipped
            case '1': // partially shipped
                $status=PlatformMarketConstService::ORDER_STATUS_UNSHIPPED;
                break;
            case '2': // shipped
            case '3': // invoiced
                $status=PlatformMarketConstService::ORDER_STATUS_DELIVERED;
                break;
            // case 'Failed':
            //  $status=PlatformMarketConstService::ORDER_STATUS_FAIL;
            //  break;
            default:
                return null;
        }
        return $status;
    }

    public function submitOrderFufillment($esgOrder,$esgOrderShipment,$platformOrderIdList)
    {
        $storeName = $platformOrderIdList[$esgOrder->platform_order_id];
        $orderItemIds = array();
        $extorderno = $esgOrder->platform_order_id;
        foreach($esgOrder->soItem as $item)
        {
            $eaitem['sellersku'] = $item->prod_sku;
            $eaitem['qty'] = $item->qty;
            $eaitem['ext_item_cd'] = $item->ext_item_cd;
            $selleritem[] = $eaitem;
        }

        if ($esgOrderShipment) {
            $response = $this->setStatusToShipped($storeName, $extorderno, $selleritem,$esgOrderShipment);
            if($response){
                $ship_status = $response['data']['Result']['OrderStatus'];
                if(!$response['error'])
                {
                    if($ship_status == 'Shipped')
                    {
                            return true;
                    } else {
                            $subject = "FAIL: UpdateShipment to MarketPlace: [{$storeName}]! ESG Order [{$esgOrder->so_no}], Newegg Order [{$esgOrder->platform_order_id}]\r\n";
                            $message = "Update failure has occur for ESG Order [{$esgOrder->so_no}], [{$storeName}] Order [{$esgOrder->platform_order_id}]\r\n";
                            $message .="Please check and update manually to avoid order cancellation by [{$storeName}] \r\n";
                            $message .= "Thanks\r\n\r\n";
                            $message .= $response;
                            $email = 'newegg@brandsconnect.net, jerry.lim@eservicesgroup.com';
                            $this->sendAlertMailMessage($email, $subject, $message);
                            return false;
                    }               
                }else{
                       $subject = "FAIL: Response Error in UpdateShipment to MarketPlace: [{$storeName}]! ESG Order [{$esgOrder->so_no}], Newegg Order [{$esgOrder->platform_order_id}]\r\n";
                       $message = "Update failure has occur for ESG Order [{$esgOrder->so_no}], [{$storeName}] Order [{$esgOrder->platform_order_id}]\r\n";
                            $message .="Please check and update manually to avoid order cancellation by [{$storeName}] \r\n";
                            $message .= "Thanks\r\n\r\n";
                            $message .= $response;
                            $email = 'jerry.lim@eservicesgroup.com';
                            $this->sendAlertMailMessage($email, $subject, $message);
                            return false;
                }
                //return $response;
            }else{
                return false;
            }
        }
    }

    public function getShipmentProviders($storeName)
    {
# copied from lazada, not yet set up for Newegg
        return false;
        
        $this->neweggShipmentProviders=new NeweggShipmentProviders($storeName);
        $result = $this->neweggShipmentProviders->fetchShipmentProviders();
        return $result;
    }

    public function setStatusToShipped($storeName, $extorderno, $selleritem, $esgOrderShipment)
    {
        $shipmentProvider = CourierInfo::where('courier_id', '=', $esgOrderShipment->courier_id)->where('status', '=', '1')->first();
        $this->neweggOrderStatus = new NeweggOrderStatus($storeName);
        $this->neweggOrderStatus->setOrderItemIds($selleritem);
        $this->neweggOrderStatus->setShipService("Air");
        $this->neweggOrderStatus->setOrderNumber($extorderno);
        $this->neweggOrderStatus->setTrackingNumber($esgOrderShipment->tracking_no);
        $this->neweggOrderStatus->setShipCarrier($shipmentProvider->courier_name);
        $orginOrderItemList=$this->neweggOrderStatus->setStatusToShipped();
        $this->saveDataToFile(serialize($orginOrderItemList),"setStatusToShipped");
        return $orginOrderItemList;
    }

    public function getShipedOrderState()
    {
        return  "Shipped";
    }

    // public function setStatusToShipped($storeName,$orderId)
    // {
    //     $this->neweggOrderStatus = new NewwggOrderStatus($storeName);
    //     $this->neweggOrderStatus->setOrderItemId($orderItemId);
    //     $orginOrderItemList=$this->neweggOrderStatus->setStatusToShipped();
    //     $this->saveDataToFile(serialize($orginOrderItemList),"setStatusToShipped");
    //     return $orginOrderItemList;
    // }

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

    private function convertFromUtcToPst($timestamp, $format = "Y-m-d")
    {
        if($timestamp) {
             // change timezone to Pacific Standard
            $dt = new \DateTime($timestamp);
            $dt->setTimezone(new \DateTimeZone("PST"));
            $dateTime = $dt->format($format);
            return $dateTime;
        }

        return "";
    }

    private function convertFromPstToUtc($timestamp, $timestampFormat = "m/d/Y H:i:s", $format = "Y-m-d H:i:s")
    {
        if($timestamp) {
            # Newegg's time is in PST
            # let DateTime know the format of your $timestamp
            $dtOrderdate = \DateTime::createFromFormat($timestampFormat, $timestamp, new \DateTimeZone("PST"));
            $dtOrderdate->setTimezone(new \DateTimeZone("UTC"));
            $utcOrderDate = $dtOrderdate->format($format);
            return $utcOrderDate;
        }

        return "";
    }

    public function getEsgShippingProvider($countryCode)
    {
/*
# copied from Lazada, pending set up
        $shipmentProvider = array(
            "MY" => "AS-Poslaju-HK",      
            "SG" => "LGS-SG3-HK",                
            "TH" => "LGS-TH3-HK",       
            "ID" => "LGS-LEX-ID-HK",
            "PH" => "AS-LBC-JZ-HK Sellers-LZ2"
        );
        if(isset($shipmentProvider[$countryCode]))
        return $shipmentProvider[$countryCode];
*/
    }

     public function sendAlertMailMessage($email, $subject,$message)
    {
        $this->sendMailMessage($email, $subject, $message);
        return false;
    }

}