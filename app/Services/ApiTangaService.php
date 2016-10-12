<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Schedule;

//use tanga api package
use App\Repository\TangaMws\TangaOrder;
use App\Repository\TangaMws\TangaOrderList;

class ApiTangaService  implements ApiPlatformInterface
{
    use ApiBaseOrderTraitService;

    public function getPlatformId()
    {
        return 'Tanga';
    }

    public function retrieveOrder($storeName,$schedule)
    {
        $this->setSchedule($schedule);
        $orginOrderList = $this->getOrderList($storeName);
        if ($orginOrderList) {
            foreach ($orginOrderList as $order) {
                if ($order) {
                    if (isset($order['shipping_name'])) {
                        $addressId = $this->updateOrCreatePlatformMarketShippingAddress($order, $storeName);
                    }

                    $this->updateOrCreatePlatformMarketOrder($order,$addressId,$storeName);

                    $originOrderItemList=$this->getOrderItemList($order,$order["order_id"]);
                    if($originOrderItemList){
                        foreach($originOrderItemList as $orderItem){
                            $this->updateOrCreatePlatformMarketOrderItem($order,$orderItem);
                        }
                    }
                }
            }

            return true;
        }
    }

    public function getOrderList($storeName)
    {
        $this->tangaOrderList = new TangaOrderList($storeName);
        $this->storeCurrency = $this->tangaOrderList->getStoreCurrency();
        $lastTime = date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
        $this->tangaOrderList->setStartAt($lastTime);
        $originOrderList = $this->tangaOrderList->fetchOrderList();
        $this->saveDataToFile(serialize($originOrderList), 'getOrderList');

        return $originOrderList;
    }

    public function getOrderItemList($order, $orderId)
    {
        $originOrderItemList = $order['line_items'];

        return $originOrderItemList;
    }

    public function submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList)
    {
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName)
    {
        $totalAmount = 0;

        foreach($order['line_items'] as $orderItem){
            $totalAmount += $orderItem['cost'] * $orderItem['quantity'];
        }

        $platformStore = $this->getPlatformStore($storeName);
        $object = [
            'platform' => $storeName,
            'biz_type' => "Tanga",
            'store_id' => $platformStore->id,
            'platform_order_id' => $order['order_id'],
            'platform_order_no' => $order['order_id'],
            // 'purchase_date' => $order['CreatedAt'],
            // 'last_update_date' => $order['UpdatedAt'],
            // 'order_status' => $orderStatus,
            // 'esg_order_status'=>$this->getSoOrderStatus($orderStatus),
            'buyer_email' => $order['order_id']."@tanga-api.com",
            'currency' => $this->storeCurrency,
            'shipping_address_id' => $addressId,
            'total_amount' => $totalAmount,
        ];

        $platformMarketOrder = PlatformMarketOrder::updateOrCreate(
            ['platform_order_id' => $order['order_id']],
            $object
        );

        return $platformMarketOrder;
    }

    public function updateOrCreatePlatformMarketOrderItem($order, $orderItem)
    {
        $object = [
            'platform_order_id' => $order['order_id'],
            'seller_sku' => $orderItem['sku_code'],
            'order_item_id' => $orderItem['line_item_id'],
            'title' => $orderItem['sku_name'],
            'quantity_ordered' => 1
        ];

        if (isset($orderItem['quantity'])) {
            $object['quantity_shipped'] = $orderItem['quantity'];
        }

        if (isset($orderItem['cost'])) {
            $object['item_price'] = $orderItem['cost'];
        }

        $platformMarketOrderItem = PlatformMarketOrderItem::updateOrCreate(
            [
                'platform_order_id' => $order['order_id'],
                'order_item_id' => $orderItem['line_item_id']
            ],
            $object
        );
    }

    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName)
    {
        $object = [];
        $object['platform_order_id']=$order['order_id'];
        $object['name'] = $order['shipping_name'];
        $object['address_line_1'] = $order['shipping_address1'];
        $object['address_line_2'] = $order['shipping_address2'];
        $object['address_line_3'] = '';
        $object['city'] = $order['shipping_city'];
        $object['county'] = $this->getCountryName(strtoupper(substr($storeName, -2)));
        $object['country_code'] = strtoupper(substr($storeName, -2));
        $object['district'] = '';
        $object['state_or_region'] = '';
        $object['postal_code'] = $order['shipping_zip'];
        $object['phone'] = $order['shipping_phone'];

        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(['platform_order_id' => $order['order_id']], $object);

        return $platformMarketShippingAddress->id;
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
    }

    public function getCountryName($code)
    {
        $country = [
            'US' => 'United States',
        ];

        return $country[$code] ?: '';
    }
}
