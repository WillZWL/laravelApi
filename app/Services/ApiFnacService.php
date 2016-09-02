<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Schedule;
use Illuminate\Support\Arr;

//use Fnac api package
use App\Repository\FnacMws\FnacOrder;
use App\Repository\FnacMws\FnacOrderList;
use App\Repository\FnacMws\FnacOrderItemList;
use App\Repository\FnacMws\FnacOrderUpdate;

class ApiFnacService extends ApiBaseService implements ApiPlatformInterface
{
    function __construct()
    {

    }

    public function getPlatformId()
    {
        return 'Fnac';
    }

    public function retrieveOrder($storeName)
    {
        $this->ackRefusedFraudOrder($storeName);

        $processCount = 0;
        if ($orginOrderList = $this->getOrderList($storeName)) {
            foreach ($orginOrderList as $order) {
                if (isset($order['shipping_address'])) {
                    $addressId = $this->updateOrCreatePlatformMarketShippingAddress($order, $storeName);
                }

                $this->updateOrCreatePlatformMarketOrder($order, $addressId, $storeName);

                if($originOrderItemList = $this->getOrderItemList($order, $order["order_id"])){
                    if (Arr::isAssoc($originOrderItemList)) {
                        $this->updateOrCreatePlatformMarketOrderItem($order, $originOrderItemList);
                    } else {
                        foreach($originOrderItemList as $orderItem){
                            $this->updateOrCreatePlatformMarketOrderItem($order, $orderItem);
                        }
                    }
                }

                $processCount += 1;
            }
        }

        $this->ackFnacAcceptedOrders($storeName);

        if ($processCount > 0) {
            return true;
        }

        return false;
    }

    public function getOrder($storeName, $orderId)
    {
        $this->fnacOrder = new FnacOrder($storeName);
        $this->storeCurrency = $this->fnacOrder->getStoreCurrency();
        $this->fnacOrder->setOrderId($orderId);
        $returnData = $this->fnacOrder->fetchOrder();

        return $returnData;
    }

    public function getOrderList($storeName)
    {
        $this->fnacOrderList = new FnacOrderList($storeName);
        $this->storeCurrency = $this->fnacOrderList->getStoreCurrency();
        $lastTime = date('c', strtotime($this->getSchedule()->last_access_time));
        $this->fnacOrderList->setMinAt($lastTime);
        $originOrderList = $this->fnacOrderList->fetchOrderList();
        $this->saveDataToFile(serialize($originOrderList),"getOrderList");

        return $originOrderList;
    }

    public function getOrderItemList($order, $orderId)
    {
        $originOrderItemList = $order['order_detail'];

        return $originOrderItemList;
    }

    public function ackRefusedFraudOrder($storeName)
    {
        $platformMarketOrders = PlatformMarketOrder::where('platform', '=', $storeName)
            ->where('acknowledge', '=', '-1')
            ->where('order_status', '!=', 'ToShip')
            ->where('order_status', '!=', 'Shipped')
            ->where('order_status', '!=', 'NotReceived')
            ->where('order_status', '!=', 'Received')
            ->get();

        if ($platformMarketOrders) {
            $fnacOrderIds = [];
            foreach ($platformMarketOrders as $order) {
                $fnacOrderIds[] = $order->platform_order_id;
            }

            $this->updateFnacOrdersStatus($storeName, $fnacOrderIds, 'Refused');
        }
    }

    public function ackFnacAcceptedOrders($storeName)
    {
        $platformMarketOrders = PlatformMarketOrder::where('platform', '=', $storeName)
            ->where('order_status', '=', 'Created')
            ->where('esg_order_status', '=', PlatformMarketConstService::ORDER_STATUS_PENDING)
            ->where('acknowledge', '=', '0')
            ->get();

        if ($platformMarketOrders) {
            $fnacOrderIds = [];
            foreach ($platformMarketOrders as $order) {
                $fnacOrderIds[] = $order->platform_order_id;
            }

            $this->updateFnacOrdersStatus($storeName, $fnacOrderIds, 'Accepted');
        }
    }

    public function updateFnacOrdersStatus($storeName, $fnacOrderIds, $orderDetailAction)
    {
        if (!$storeName || !$orderDetailAction || !$fnacOrderIds) {
            return false;
        }

        $orderAction = 'accept_all_orders';

        $this->fnacOrderUpdate = new fnacOrderUpdate($storeName);
        $this->fnacOrderUpdate->setFnacOrderIds($fnacOrderIds);
        $this->fnacOrderUpdate->setOrderAction($orderAction);
        $this->fnacOrderUpdate->setOrderDetailAction($orderDetailAction);

        if ($responseDataList = $this->fnacOrderUpdate->updateFnacOrdersStatus()) {

            foreach ($responseDataList as $responseData) {
                if ($responseData['status'] == 'OK' && $responseData['state'] == $orderDetailAction) {
                    try {
                        $platformMarketOrder = PlatformMarketOrder::where('platform_order_id', '=', $responseData['order_id'])
                            ->firstOrFail();
                        if ($platformMarketOrder) {
                            $platformMarketOrder->order_status = $responseData['state'];
                            $platformMarketOrder->save();
                        }
                    } catch(Exception $e) {
                        echo 'Message: ' .$e->getMessage();
                    }
                }
            }

            $this->saveDataToFile(serialize($responseDataList),"responseFnacOrder". $orderDetailAction);
        }
    }

    public function updatePendingPaymentStatus($storeName)
    {
        $pendingPaymentOrderList = PlatformMarketOrder::where('platform', '=', $storeName)
                            ->where('esg_order_status', '=', PlatformMarketConstService::ORDER_STATUS_PENDING)
                            ->get();
        if ($pendingPaymentOrderList) {
            $fnacOrderIds = [];
            foreach ($pendingPaymentOrderList as $pendingOrder) {
                $fnacOrderIds[] = $pendingOrder->platform_order_id;
            }

            $this->fnacOrderList = new FnacOrderList($storeName);
            $this->fnacOrderList->setFnacOrderIds($fnacOrderIds);

            if ($responseOrderList = $this->fnacOrderList->requestFnacPendingPayment()) {
                $this->updateOrderPendingPaymentStatus($responseOrderList);

                $this->saveDataToFile(serialize($responseOrderList),"responseFnacPendingPayment");
            }
        }
    }

    public function submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList)
    {
        if ($esgOrderShipment) {
            $orderAction = 'confirm_all_to_send';
            $orderDetailAction = 'Shipped';
            $fnacOrderId = $esgOrder->platform_order_id;
            $storeName = $platformOrderIdList[$fnacOrderId];

            $this->fnacOrderUpdate = new fnacOrderUpdate($storeName);
            $this->fnacOrderUpdate->setOrderId($fnacOrderId);
            $this->fnacOrderUpdate->setTrackingNumber($esgOrderShipment->tracking_no);
            $this->fnacOrderUpdate->setCourierName($esgOrderShipment->courier_name);
            $this->fnacOrderUpdate->setOrderAction($orderAction);
            $this->fnacOrderUpdate->setOrderDetailAction($orderDetailAction);

            if ($responseData = $this->fnacOrderUpdate->updateTrackingNumber()) {

                $this->saveDataToFile(serialize($responseData),"responseFnacOrderTracking");

                if ($responseData['status'] == 'OK'
                    && $responseData['state'] == 'Shipped'
                    && $responseData['order_id'] == $fnacOrderId
                ) {
                    try {
                        $platformMarketOrder = PlatformMarketOrder::where('platform_order_id', '=', $responseData['order_id'])
                            ->where('order_status', '=', 'ToShip')
                            ->firstOrFail();
                        if ($platformMarketOrder) {
                            $platformMarketOrder->order_status = $responseData['state'];
                            $platformMarketOrder->esg_order_status = $this->getSoOrderStatus($responseData['state']);
                            $platformMarketOrder->save();

                            return true;
                        }
                    } catch(Exception $e) {
                        echo 'Message: ' .$e->getMessage();
                    }
                }
            }
        }

        return false;
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName)
    {
        $itemCost = 0;

        if (Arr::isAssoc($order['order_detail'])) {
            $itemCost = $order['order_detail']['price'] + $order['order_detail']['shipping_price'];
        } else {
            foreach($order['order_detail'] as $orderItem){
                $itemCost += $orderItem['price'] * $orderItem['quantity'] + $orderItem['shipping_price'];
            }
        }

        $totalAmount = $itemCost;

        $object = [
            'platform' => $storeName,
            'biz_type' => "Fnac",
            'platform_order_id' => $order['order_id'],
            'platform_order_no' => $order['order_id'],
            'purchase_date' => $order['created_at'],
            'last_update_date' => '0000-00-00 00:00:00',
            'order_status' => $order['state'],
            'esg_order_status'=>$this->getSoOrderStatus($order['state']),
            'buyer_email' => $order['order_id']."@fnac-api.com",
            'currency' => $this->storeCurrency,
            'shipping_address_id' => $addressId,
            'total_amount' => $totalAmount,
            'payment_method' => 'fnac_'. strtolower(substr($storeName, -2)),
        ];

        if (isset($order['client_firstname'])) {
            $object['buyer_name'] = $order['client_firstname'];
        }

        if (isset($order['client_lastname'])) {
            $object['buyer_name'] .=" ".$order['client_lastname'];
        }

        if (isset($order['nb_messages'])){
            $object['remarks'] = $order['nb_messages'];
        }

        $platformMarketOrder = PlatformMarketOrder::updateOrCreate(['platform_order_id' => $order['order_id']], $object);

        return $platformMarketOrder;
    }

    public function updateOrCreatePlatformMarketOrderItem($order, $orderItem)
    {
        $object = [
            'platform_order_id' => $order['order_id'],
            'seller_sku' => $orderItem['offer_seller_id'],
            'order_item_id' => $orderItem['order_detail_id'],
            'title' => $orderItem['product_name'],
            'quantity_ordered' => 1
        ];

        if (isset($orderItem['quantity'])) {
            $object['quantity_shipped'] = $orderItem['quantity'];
        }

        if (isset($orderItem['price'])) {
            $object['item_price'] = $orderItem['price'];
        }

        if (isset($orderItem['shipping_price'])) {
            $object['shipping_price'] = $orderItem['shipping_price'];
        }

        if (isset($orderItem['fees'])) {
            $object['item_tax'] = $orderItem['fees'];
        }

        if (isset($orderItem['state'])) {
            $object['status'] = studly_case($orderItem['state']);
        }

        if (isset($orderItem['shipping_method'])) {
            $object['ship_service_level'] = $this->shipServiceLevel($orderItem['shipping_method']);
        }

        if (isset($orderItem['tracking_number'])) {
            $object['tracking_code'] = $orderItem['tracking_number'];
        }

        if (isset($orderItem['internal_comment'])) {
            $object['reason'] = $orderItem['internal_comment'];
        }

        $platformMarketOrderItem = PlatformMarketOrderItem::updateOrCreate(
            [
                'platform_order_id' => $order['order_id'],
                'order_item_id' => $orderItem['order_detail_id']
            ],
            $object
        );
    }

    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName)
    {
        $object = [];
        $object['platform_order_id']=$order['order_id'];
        $object['name'] = $order['shipping_address']['firstname']." ".$order['shipping_address']['lastname'];
        $object['address_line_1'] = $order['shipping_address']['address1'];
        $object['address_line_2'] = $order['shipping_address']['address2'];
        $object['address_line_3'] = $order['shipping_address']['address3'];
        $object['city'] = $order['shipping_address']['city'];
        $object['county'] = $order['shipping_address']['country'];
        $object['country_code'] = strtoupper(substr($storeName, -2));
        $object['district'] = '';
        $object['state_or_region'] = '';
        $object['postal_code'] = $order['shipping_address']['zipcode'];
        $object['phone'] = $order['shipping_address']['phone'];

        $object['bill_name'] = $order['billing_address']['firstname']." ".$order['billing_address']['lastname'];
        $object['bill_address_line_1'] = $order['billing_address']['address1'];
        $object['bill_address_line_2'] = $order['billing_address']['address2'];
        $object['bill_address_line_3'] = $order['billing_address']['address3'];
        $object['bill_city'] = $order['billing_address']['city'];
        $object['bill_county'] = $order['billing_address']['country'];
        $object['bill_country_code'] = strtoupper(substr($storeName, -2));
        $object['bill_district'] = '';
        $object['bill_state_or_region'] = '';
        $object['bill_postal_code'] = $order['billing_address']['zipcode'];
        $object['bill_phone'] = $order['billing_address']['phone'];

        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(['platform_order_id' => $order['order_id']], $object);

        return $platformMarketShippingAddress->id;
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
        switch ($platformOrderStatus) {
            case 'Created':
            case 'Accepted':
                $status = PlatformMarketConstService::ORDER_STATUS_PENDING;
                break;

            case 'ToShip':
                $status = PlatformMarketConstService::ORDER_STATUS_UNSHIPPED;
                break;

            case 'NotReceived':
            case 'Shipped':
                $status = PlatformMarketConstService::ORDER_STATUS_SHIPPED;
                break;

            case 'Received':
                $status = PlatformMarketConstService::ORDER_STATUS_DELIVERED;
                break;

            case 'Refused':
            case 'Cancelled':
                $status = PlatformMarketConstService::ORDER_STATUS_CANCEL;
                break;

            case 'Refunded':
                $status = PlatformMarketConstService::ORDER_STATUS_RETURENED;
                break;

            case 'Error':
                $status = PlatformMarketConstService::ORDER_STATUS_FAIL;
                break;

            default:
                $status = '';
                break;
        }

        return $status;
    }

    public function shipServiceLevel($method)
    {
        switch ($method) {
            case '20':
                $method = 'Standard Shipping method';
                break;

            case '21':
                $method = 'Standard Shipping method with tracking number';
                break;

            case '22':
                $method = 'Certified Shipping method';
                break;

            case '50':
                $method = 'Fast delivery Shipping method';
                break;

            case '51':
                $method = 'Custom Shipping large products method #1';
                break;

            case '52':
                $method = 'Custom Shipping large products method #2';
                break;

            case '53':
                $method = 'Custom Shipping large products method #3';
                break;

            case '54':
                $method = 'Custom Shipping large products method #4';
                break;

            case '55':
                $method = 'Delivery point (partner Relais Colis) Shipping method';
                break;

            default:
                $method = 'Certified Shipping method';
                break;
        }

        return $method;
    }

    public function updateOrderPendingPaymentStatus($responseOrderList)
    {
        foreach ($responseOrderList as $order) {
            switch($order['state']) {
                case "Accepted":
                    break;

                case "ToShip":
                case "Cancelled":
                    try {
                        $platformMarketOrder = PlatformMarketOrder::where('platform_order_id', '=', $order['order_id'])
                            ->where('esg_order_status', '=', PlatformMarketConstService::ORDER_STATUS_PENDING)
                            ->firstOrFail();
                        if ($platformMarketOrder) {
                            $platformMarketOrder->order_status = $order['state'];
                            $platformMarketOrder->esg_order_status = $this->getSoOrderStatus($order['state']);
                            $platformMarketOrder->save();
                        }
                    } catch(Exception $e) {
                        echo 'Message: ' .$e->getMessage();
                    }
                    break;

                default:
            }
        }
    }

}