<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use Config;
use Peron\AmazonMws\AmazonOrder;
use Peron\AmazonMws\AmazonOrderList;
use Peron\AmazonMws\AmazonOrderItemList;
use Peron\AmazonMws\AmazonFeed;

class ApiAmazonService extends ApiBaseService implements ApiPlatformInterface
{
    public function __construct()
    {
    }

    public function getPlatformId()
    {
        return 'Amazon';
    }

    public function retrieveOrder($storeName)
    {
        $orginOrderList = $this->getOrderList($storeName);
        if ($orginOrderList) {
            foreach ($orginOrderList as $orderData) {
                $order = $orderData->getData();
                if (isset($order['ShippingAddress'])) {
                    $addressId = $this->updateOrCreatePlatformMarketShippingAddress($order);
                }
                $this->updateOrCreatePlatformMarketOrder($order, $addressId, $storeName);
                $originOrderItemList = $this->getOrderItemList($storeName, $order['AmazonOrderId']);
                if ($originOrderItemList) {
                    foreach ($originOrderItemList as $orderItem) {
                        $this->updateOrCreatePlatformMarketOrderItem($order, $orderItem);
                    }
                }
            }

            return true;
        }
    }

    public function getOrder($storeName, $orderId)
    {
        $this->amazonOrder = new AmazonOrder($storeName);
        $this->amazonOrder->setOrderId($orderId);
        $returnData = $this->amazonOrder->fetchOrder();

        return $returnData;
    }

    public function getOrderList($storeName)
    {
        $this->amazonOrderList = new AmazonOrderList($storeName);
        $this->amazonOrderList->setLimits('Modified', $this->getSchedule()->last_access_time);
        $this->amazonOrderList->setUseToken();
        $results = $this->amazonOrderList->fetchOrders();
        if ($results === false) {
            return false;
        }
        $orginOrderList = $this->amazonOrderList->getList();
        $this->saveDataToFile(serialize($orginOrderList), 'getOrderList');

        return $orginOrderList;
    }

    public function getOrderItemList($storeName, $orderId)
    {
        if (!isset($this->amazonOrderItemList)) {
            $this->amazonOrderItemList = new AmazonOrderItemList($storeName);
        }
        $this->amazonOrderItemList->setOrderId($orderId);
        $this->amazonOrderItemList->setUseToken();
        $this->amazonOrderItemList->fetchItems();
        $originOrderItemList = $this->amazonOrderItemList->getItems();
        $this->saveDataToFile(serialize($originOrderItemList), 'getOrderItemList');

        return $originOrderItemList;
    }

    public function submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList)
    {
        $stores = Config::get('amazon-mws.store');
        if ($esgOrderShipment) {
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">';
            $xml .= '<Header>';
            $xml .= '<DocumentVersion>1.01</DocumentVersion>';
            $xml .= '<MerchantIdentifier>'.$stores[$platformOrderIdList[$esgOrder->platform_order_id]]['merchantId'].'</MerchantIdentifier>';
            $xml .= '</Header>';
            $xml .= '<MessageType>OrderFulfillment</MessageType>';
            $xml .= '<Message>';
            $xml .= '<MessageID>1</MessageID>';
            $xml .= '<OrderFulfillment>';
            $xml .= '<AmazonOrderID>'.$esgOrder->platform_order_id.'</AmazonOrderID>';
            $xml .= '<MerchantFulfillmentID>'.$esgOrder->so_no.'</MerchantFulfillmentID>';
            $xml .= '<FulfillmentDate>'.Carbon::parse($esgOrder->dispatch_date)->format('c').'</FulfillmentDate>';
            $xml .= '<FulfillmentData>';
            $xml .= '<CarrierName>'.$esgOrderShipment->courierInfo->courier_name.'</CarrierName>';
            $xml .= '<ShippingMethod>Standard</ShippingMethod>';
            $xml .= '<ShipperTrackingNumber>'.$esgOrderShipment->tracking_no.'</ShipperTrackingNumber>';
            $xml .= '</FulfillmentData>';
            $xml .= '</OrderFulfillment>';
            $xml .= '</Message>';
            $xml .= '</AmazonEnvelope>';

            $feed = new AmazonFeed($platformOrderIdList[$esgOrder->platform_order_id]);
            $feed->setFeedType('_POST_ORDER_FULFILLMENT_DATA_');
            $feed->setFeedContent($xml);

            if ($feed->submitFeed() === false) {
                return false;
            } else {
                return $feed->getResponse();
            }
        }
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName)
    {
        $object = [
            'platform' => $storeName,
            'biz_type' => 'Amazon',
            'platform_order_id' => $order['AmazonOrderId'],
            'purchase_date' => $order['PurchaseDate'],
            'last_update_date' => $order['LastUpdateDate'],
            'order_status' => $order['OrderStatus'],
            'esg_order_status' => $this->getSoOrderStatus($order['OrderStatus']),
            'shipping_address_id' => $addressId,
        ];
        if (isset($order['FulfillmentChannel'])) {
            $object['fulfillment_channel'] = $order['FulfillmentChannel'];
        }
        if (isset($order['SalesChannel'])) {
            $object['sales_channel'] = $order['SalesChannel'];
        }
        if (isset($order['ShipServiceLevel'])) {
            $object['ship_service_level'] = $order['ShipServiceLevel'];
        }
        if (isset($order['OrderTotal'])) {
            $object['total_amount'] = $order['OrderTotal']['Amount'];
        }
        if (isset($order['OrderTotal']['CurrencyCode'])) {
            $object['currency'] = $order['OrderTotal']['CurrencyCode'];
        }
        if (isset($order['NumberOfItemsShipped'])) {
            $object['number_of_items_shipped'] = $order['NumberOfItemsShipped'];
        }
        if (isset($order['NumberOfItemsUnshipped'])) {
            $object['number_of_items_unshippped'] = $order['NumberOfItemsUnshipped'];
        }
        if (isset($order['PaymentMethod'])) {
            $object['payment_method'] = $order['PaymentMethod'];
        }
        if (isset($order['BuyerName'])) {
            $object['buyer_name'] = $order['BuyerName'];
        }
        if (isset($order['BuyerEmail'])) {
            $object['buyer_email'] = $order['BuyerEmail'];
        }
        if (isset($order['EarliestShipDate'])) {
            $object['earliest_ship_date'] = $order['EarliestShipDate'];
        }
        if (isset($order['LatestShipDate'])) {
            $object['latest_ship_date'] = $order['LatestShipDate'];
        }
        if (isset($order['EarliestDeliveryDate'])) {
            $object['earliest_delivery_date'] = $order['EarliestDeliveryDate'];
        }
        if (isset($order['LatestDeliveryDate'])) {
            $object['latest_delivery_date'] = $order['LatestDeliveryDate'];
        }
        $amazonOrder = PlatformMarketOrder::updateOrCreate(
            ['platform_order_id' => $order['AmazonOrderId']],
            $object
        );

        return $amazonOrder;
    }

    public function updateOrCreatePlatformMarketOrderItem($order, $orderItem)
    {
        $object = [
            'platform_order_id' => $order['AmazonOrderId'],
            'asin' => $orderItem['ASIN'],
            'seller_sku' => $orderItem['SellerSKU'],
            'order_item_id' => $orderItem['OrderItemId'],
            'title' => $orderItem['Title'],
            'quantity_ordered' => $orderItem['QuantityOrdered'],
        ];
        if (isset($orderItem['QuantityShipped'])) {
            $object['quantity_shipped'] = $orderItem['QuantityShipped'];
        }
        if (isset($orderItem['ItemPrice'])) {
            $object['item_price'] = $orderItem['ItemPrice']['Amount'];
        }
        if (isset($orderItem['ShippingPrice'])) {
            $object['shipping_price'] = $orderItem['ShippingPrice']['Amount'];
        }
        if (isset($orderItem['GiftWrapPrice'])) {
            $object['gift_wrap_price'] = $orderItem['GiftWrapPrice']['Amount'];
        }
        if (isset($orderItem['ItemTax'])) {
            $object['item_tax'] = $orderItem['ItemTax']['Amount'];
        }
        if (isset($orderItem['ShippingTax'])) {
            $object['shipping_tax'] = $orderItem['ShippingTax']['Amount'];
        }
        if (isset($orderItem['GiftWrapTax'])) {
            $object['gift_wrap_tax'] = $orderItem['GiftWrapTax']['Amount'];
        }
        if (isset($orderItem['ShippingDiscount'])) {
            $object['shipping_discount'] = $orderItem['ShippingDiscount']['Amount'];
        }
        if (isset($orderItem['PromotionDiscount'])) {
            $object['promotion_discount'] = $orderItem['PromotionDiscount']['Amount'];
        }

        $amazonOrderItem = PlatformMarketOrderItem::updateOrCreate(
            [
                'platform_order_id' => $order['AmazonOrderId'],
                'order_item_id' => $orderItem['OrderItemId'],
            ],
            $object
        );

        return $amazonOrderItem;
    }

    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName = null)
    {
        $object = array();
        $object['platform_order_id'] = $order['AmazonOrderId'];
        $object['platform_order_no'] = $order['AmazonOrderId'];
        $object['name'] = $order['ShippingAddress']['Name'];
        $object['address_line_1'] = $order['ShippingAddress']['AddressLine1'];
        $object['address_line_2'] = $order['ShippingAddress']['AddressLine2'];
        $object['address_line_3'] = $order['ShippingAddress']['AddressLine3'];
        $object['city'] = $order['ShippingAddress']['City'];
        $object['county'] = $order['ShippingAddress']['County'];
        $object['district'] = $order['ShippingAddress']['District'];
        $object['state_or_region'] = $order['ShippingAddress']['StateOrRegion'];
        $object['postal_code'] = $order['ShippingAddress']['PostalCode'];
        $object['country_code'] = $order['ShippingAddress']['CountryCode'];
        $object['phone'] = $order['ShippingAddress']['Phone'];
        $object['bill_country_code'] = $order['ShippingAddress']['CountryCode'];

        $amazonOrderShippingAddress = PlatformMarketShippingAddress::updateOrCreate(['platform_order_id' => $order['AmazonOrderId']], $object);

        return $amazonOrderShippingAddress->id;
    }

    public function getShipedOrderState()
    {
        return  "Shipped";
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
        switch ($platformOrderStatus) {
            case 'Canceled':
                $status = PlatformMarketConstService::ORDER_STATUS_CANCEL;
                break;
            case 'Pending':
                $status = PlatformMarketConstService::ORDER_STATUS_PENDING;
                break;
            case 'Shipped':
                $status = PlatformMarketConstService::ORDER_STATUS_SHIPPED;
                break;
            case 'Unshipped':
                $status = PlatformMarketConstService::ORDER_STATUS_UNSHIPPED;
                break;
            case 'Delivered':
                $status = PlatformMarketConstService::ORDER_STATUS_DELIVERED;
                break;
            case 'Failed':
                $status = PlatformMarketConstService::ORDER_STATUS_FAIL;
                break;
            default:
                $status = 1;
        }

        return $status;
    }
}
