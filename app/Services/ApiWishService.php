<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use Config;

//use Wish api SDK
use Wish\Model\WishTracker;
use Wish\Exception\OrderAlreadyFulfilledException;
use Wish\Model\WishReason;

class ApiWishService implements ApiPlatformInterface
{
    use ApiBaseOrderTraitService;

    public function __construct()
    {
        parent::__construct();
    }

    public function getPlatformId()
    {
        return 'Wish';
    }

    public function retrieveOrder($storeName,$schedule)
    {
        $this->setSchedule($schedule);
        $orginOrderList = $this->getOrderList($storeName);
        // Now no any order, Temporarily cannot take data, No clearly Wish order item fotmat, go on deal with Wish after wait a new order
        if ($orginOrderList) {
            foreach ($orginOrderList as $order) {
                if (isset($order['ShippingDetail'])) {
                    $addressId = $this->updateOrCreatePlatformMarketShippingAddress($order, $storeName);
                }
                if ($addressId) {
                    $this->updateOrCreatePlatformMarketOrder($order, $addressId, $storeName);
                }
            }
            return true;
        }
    }

    public function getOrderList($storeName)
    {
        $wishClient = $this->initWishClient($storeName);

        $dateTime=date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
        $originOrderList = $wishClient->getAllChangedOrdersSince($dateTime);
        /* $this->saveDataToFile(serialize($originOrderList), 'getOrderList');*/
        return $originOrderList;

        $orders = $client->getAllChangedOrdersSince('2010-01-20');
    }

    public function submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList)
    {
        return false;//testing
        $storeName = $platformOrderIdList[$esgOrder->platform_order_id];
        $wishClient = $this->initWishClient($storeName);
        if ($esgOrderShipment) {
            $courier = $this->getWishCourier($esgOrderShipment->courierInfo->aftership_id);
            $tracker = new WishTracker($courier,$esgOrderShipment->tracking_no, $message);
            $wishClient->fulfillOrderById($orderId,$tracker);
        }
    }

    public function getWishCourier($courier)
    {
        switch ($courier) {
            case 'dhl':
            case 'dhl-global-mail':
                $wishCourier = array('transporter_name' => 'DHL');
                break;
            case 'dpd':
                $wishCourier = array('transporter_name' => 'DPD');
                break;
            default:
                // code...
                break;
        }
        return $wishCourier;
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName)
    {
        // default 1st order item status as order status
        $platformStore = $this->getPlatformStore($storeName);
        $object = [
            //'platform' => $storeName,
            'biz_type' => $this->getPlatformId(),
            'store_id' => $platformStore->id,
            'platform_order_id' => $order['order_id'],
            'platform_order_no' => $order['transaction_id'],
            'purchase_date' => $purchasedate,
            'last_update_date' =>  $order['last_updated'],
            'order_status' =>  $order['state'],
            'esg_order_status' => $this->getSoOrderStatus($order['state']),
            'buyer_email' => $order['buyer_id'].'@wish-api.com',
            'buyer_name' => $order['ShippingDetail']['name'],
            'currency' => $this->storeCurrency,
            'shipping_address_id' => $addressId,
            'total_amount' => $order['order_total'],
            //'earliest_ship_date' => $order['days_to_fulfill'],
            //'latest_ship_date' => $order['order_total'],
        ];

        $platformMarketOrder = PlatformMarketOrder::updateOrCreate(
            [
                'platform_order_id' => $order['order_id'],
                'platform' => $storeName,
            ],
            $object
        );
    }

    public function updateOrCreatePlatformMarketOrderItem($platformMarketOrderId, $order, $orderItem, $storeName)
    {

    }

    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName)
    {
        $object = [];
        $object['platform_order_id'] = $order['order_id'];
        $deliveryInfo = $order['ShippingDetail'];
        $object['name'] = (string) $deliveryInfo['name'];
        $object['address_line_1'] = (string) $deliveryInfo['street_address1'];
        $object['city'] = (string) $deliveryInfo['city'];
        $object['county'] = (string) $deliveryInfo['country'];
        $object['country_code'] = $this->getEsgCountryCode($deliveryInfo['county']);
        $object['bill_country_code'] = $this->getEsgCountryCode($deliveryInfo['county']);
        $object['state_or_region'] = $deliveryInfo['state'];
        $object['phone'] = $deliveryInfo['phone_number'];
        $object['postal_code'] = $deliveryInfo['zipcode'];

        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(
            [
                'platform_order_id' => $order['order_id'],
                'platform' => $storeName
            ],
            $object
        );
        return $platformMarketShippingAddress->id;
    }

    public function getSoOrderStatus($platformOrderStatus)
    {

    }

    public function getEsgCountryCode($countryalpha2)
    {
        $countryCode = array(
            'FX' => 'FR',
            'DE' => 'DE',
         );
        if (isset($countryCode[$countryalpha2])) {
            return $countryCode[$countryalpha2];
        } else {
            return $countryalpha2;
        }
    }
}
