<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Schedule;

//use qoo10 api package
use App\Repository\Qoo10Mws\Qoo10Order;
use App\Repository\Qoo10Mws\Qoo10OrderList;
// use App\Repository\Qoo10Mws\Qoo10OrderUpdate;

class ApiQoo10Service  implements ApiPlatformInterface
{
    use ApiBaseOrderTraitService;

    public function getPlatformId()
    {
        return 'Qoo10';
    }

    public function retrieveOrder($storeName,$schedule)
    {
        $this->setSchedule($schedule);
        $orginOrderList = $this->getOrderList($storeName);
        $totalOrder = $orginOrderList['TotalOrder'];
        if ($totalOrder) {
            for ($i=1; $i <= $totalOrder; $i++) {
                $order = $orginOrderList['Order'. $i];
                if ($order) {

                    if (isset($order['Addr1']) || isset($order['Addr2'])) {
                        $addressId = $this->updateOrCreatePlatformMarketShippingAddress($order, $storeName);
                    }

                    if (isset($addressId)) {
                        if ($this->updateOrCreatePlatformMarketOrder($order,$addressId,$storeName)) {
                            $this->updateOrCreatePlatformMarketOrderItem($order);
                        }
                    }

                }
            }

            $this->updateOrderItemSellerSku($storeName);

            return true;
        }
    }

    public function updateOrderItemSellerSku($storeName)
    {
        $waitOrderItemList = PlatformMarketOrder::join('platform_market_order_item AS item', 'item.platform_order_id', '=', 'platform_market_order.platform_order_id')
            ->where('acknowledge', '=', '0')
            ->where('biz_type', '=', 'Qoo10')
            ->where('platform', '=', $storeName)
            ->where('item.seller_sku', '=', '')
            ->select('item.*')
            ->get();

        if ($waitOrderItemList) {
            $this->apiQoo10ProductService = new ApiQoo10ProductService($storeName);

            $msgNote = "";
            foreach ($waitOrderItemList as $waitOrderItem) {
                // $orderInfo = $this->getOrder($waitOrderItem->platform_order_no, $storeName);
                $itemCode = $waitOrderItem->marketplace_sku;
                $sellerCode = "";
                if ($product = $this->apiQoo10ProductService->getProduct($itemCode, $storeName)) {
                    if (isset($product['ResultObject'])
                        && isset($product['ResultObject']['ItemDetailInfo'])
                        && isset($product['ResultObject']['ItemDetailInfo']['SellerCode'])
                    ) {
                       $sellerCode = $product['ResultObject']['ItemDetailInfo']['SellerCode'];
                    }
                }

                if ($sellerCode) {
                    $orderItem = PlatformMarketOrderItem::find($waitOrderItem->id);
                    $orderItem->seller_sku = $sellerCode;
                    $orderItem->save();
                } else {
                    $msgNote .= "QOO10 Order No: ". $waitOrderItem->platform_order_id .", QOO10 Item Code: ". $itemCode .";\r\n";
                }
            }

            if ($msgNote != "") {
                $to = 'qoo10sg@brandsconnect.net, celine@eservicesgroup.net';
                $header = "From: admin@eservicesgroup.com\r\n";
                $header .= "Cc: brave.liu@eservicesgroup.com\r\n";
                $message = "Here orders import to ESG failed and skipped, Need first to do seller item code with QOO10 item code mapping, And Contact IT Support\r\n" . $msgNote;
                mail($to, "QOO10 Item Code with Seller Item Code Lack Mapping", $message, $header);

                return false;
            }
        }
    }

    public function getOrder($orderNo, $storeName)
    {
        $this->qoo10Order = new Qoo10Order($storeName);
        $this->qoo10Order->setOrderId($orderNo);
        $responseOrderData = $this->qoo10Order->fetchOrder();
        $this->saveDataToFile(serialize($responseOrderData), 'responseOrderData');

        return $responseOrderData;
    }

    public function getOrderList($storeName)
    {
        $this->qoo10OrderList = new Qoo10OrderList($storeName);
        $this->storeCurrency = $this->qoo10OrderList->getStoreCurrency();
        $lastTime = date("Ymd", strtotime($this->getSchedule()->last_access_time));
        $this->qoo10OrderList->setStartDate($lastTime);
        $responseOrderListData = $this->qoo10OrderList->fetchOrderList();
        $this->saveDataToFile(serialize($responseOrderListData), 'responseOrderListData');
        return $responseOrderListData;
    }

    public function submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList)
    {
        // $storeName = $platformOrderIdList[$esgOrder->platform_order_id];
        // $platform_order_id = $esgOrder->platform_order_id;
        // $tracking_no = $esgOrderShipment->tracking_no;


        // $this->qoo10OrderUpdate = new Qoo10OrderUpdate($storeName);
        // $this->qoo10OrderUpdate->setOrderId($platform_order_id);
        // $this->qoo10OrderUpdate->setTrackingNumber($tracking_no);
        // $requestData = $this->qoo10OrderUpdate->getRequestTrackingData();
        // $this->saveDataToFile(serialize($requestData),"requestQoo10OrderTracking");

        // $responseData = $this->qoo10OrderUpdate->updateTrackingNumber($requestData);
        // $this->saveDataToFile(serialize($responseData),"responseQoo10OrderTracking");

        // if ( isset($responseData['shipment']) && $responseData['shipment']['tracking_number'] == $tracking_no) {
        //     return true;
        // }

        // if ( isset($responseData['error']) ) {
        //     $email = 'brave.liu@eservicesgroup.com';
        //     $subject = "Error, import tracking to qoo10";
        //     $msg = $responseData['error'] . "\r\n\r\nplatform_order_id: $platform_order_id, tracking_no: $tracking_no";
        //     $this->sendMailMessage($email, $subject, $msg);

        //     return false;
        // }

        // return false;
    }

    public function getShipedOrderState()
    {
        return  "Shipped";
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName)
    {
        $platformStore = $this->getPlatformStore($storeName);

        $object = [
            'platform' => $storeName,
            'biz_type' => "Qoo10",
            'store_id' => $platformStore->id,
            'platform_order_id' => $order['packNo'],
            'platform_order_no' => $order['orderNo'],
            'purchase_date' => $order['orderDate'] ? $order['orderDate'] : "0000-00-00 00:00:00",
            'last_update_date' => '0000-00-00 00:00:00',
            'order_status' => $order['shippingStatus'],
            'esg_order_status'=>$this->getSoOrderStatus($order['shippingStatus']),
            'buyer_name' => $order['buyer'],
            'buyer_email' => $order['buyerEmail'],
            'currency' => $order['Currency'],
            'shipping_address_id' => $addressId,
            'ship_service_level' => $order['shippingRateType'],
            'total_amount' => $order['total'],
            'payment_method' => $order['PaymentMethod'],
            'earliest_ship_date' => !is_array($order['EstShippingDate']) ? $order['EstShippingDate'] : "",
            'latest_ship_date' => !is_array($order['ShippingDate']) ? $order['ShippingDate'] : "",
            'earliest_delivery_date' => !is_array($order['ShippingDate']) ? $order['ShippingDate'] : "",
            'latest_delivery_date' => !is_array($order['DeliveredDate']) ? $order['DeliveredDate'] : "",
        ];

        $platformMarketOrder = PlatformMarketOrder::updateOrCreate(
            [
                'platform_order_id' => $order['packNo'],
            ],
            $object
        );

        return $platformMarketOrder;
    }

    public function updateOrCreatePlatformMarketOrderItem($order, $orderItem="")
    {
        $object = [
            'platform_order_id' => $order['packNo'],
            'order_item_id' => $order['packNo'],
            'seller_sku' => $order['sellerItemCode'],
            'marketplace_sku' => $order['itemCode'],
            'title' => $order['itemTitle'],
            'quantity_ordered' => 1,
            'item_price' => $order['orderPrice'],
            'quantity_ordered' => $order['orderQty'],
            'promotion_discount' => $order['discount'],
            'package_id' => $order['PackingNo']
        ];

        $platformMarketOrderItem = PlatformMarketOrderItem::updateOrCreate(
            [
                'platform_order_id' => $order['packNo'],
            ],
            $object
        );
    }

    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName)
    {
        $object = [];
        $object['platform_order_id']=$order['packNo'];
        $object['name'] = $order['receiver'];
        $object['address_line_1'] = $order['Addr1'];
        $object['address_line_2'] = $order['Addr2'];
        $object['address_line_3'] = '';
        $object['city'] = '';
        $object['county'] = $order['shippingCountry'];
        $object['country_code'] = strtoupper(substr($storeName, -2));
        $object['district'] = '';
        $object['state_or_region'] = '';
        $object['postal_code'] = $order['zipCode'];
        $object['phone'] = $order['receiverMobile'] ;
        $object['bill_name'] = $order['buyer'] ;
        $object['bill_address_line_1'] = $order['Addr1'] ;
        $object['bill_address_line_2'] = $order['Addr2'] ;
        $object['bill_address_line_3'] = '' ;
        $object['bill_county'] = $order['shippingCountry'] ;
        $object['bill_postal_code'] = $order['zipCode'] ;
        $object['bill_country_code'] = strtoupper(substr($storeName, -2));
        $object['bill_phone'] = $order['buyerMobile'];

        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(['platform_order_id' => $order['packNo']], $object);

        return $platformMarketShippingAddress->id;
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
        switch ($platformOrderStatus) {
            case 'On Waiting(1)':
                $status = PlatformMarketConstService::ORDER_STATUS_NEW;
                break;

            case 'On request(2)':
                $status = PlatformMarketConstService::ORDER_STATUS_PAID;
                break;

            case 'Seller confirm(3)':
                $status = PlatformMarketConstService::ORDER_STATUS_UNSHIPPED;
                break;


            case 'On delivery(4)':
                $status = PlatformMarketConstService::ORDER_STATUS_SHIPPED;
                break;

            case 'Delivered(5)':
                $status = PlatformMarketConstService::ORDER_STATUS_DELIVERED;
                break;

            default:
                $status = '';
                break;
        }

        return $status;
    }

}
