<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use Illuminate\Support\Arr;
//use priceminister api package
use App\Repository\PriceMinisterMws\PriceMinisterOrder;
use App\Repository\PriceMinisterMws\PriceMinisterOrderList;
use App\Repository\PriceMinisterMws\PriceMinisterOrderInfo;
use App\Repository\PriceMinisterMws\PriceMinisterOrderTracking;

class ApiPriceMinisterService implements ApiPlatformInterface
{
    use ApiBaseOrderTraitService;
    private $storeCurrency;

    public function getPlatformId()
    {
        return 'PriceMinister';
    }

    public function retrieveOrder($storeName,$schedule)
    {
        $this->setSchedule($schedule);
        $originOrderList = $this->getOrderList($storeName);
        if ($originOrderList) {
            foreach ($originOrderList as $order) {
                if (isset($order['deliveryinformation']['deliveryaddress'])) {
                    $addressId = $this->updateOrCreatePlatformMarketShippingAddress($order, $storeName);
                }
                if ($addressId) {
                    $this->updateOrCreatePlatformMarketOrder($order, $addressId, $storeName);
                    $originOrderItemList = $this->getOrderItemList($order, $order['purchaseid']);
                    if ($originOrderItemList) {
                        foreach ($originOrderItemList as $orderItem) {
                            if (Arr::isAssoc($orderItem)) {
                                $this->updateOrCreatePlatformMarketOrderItem($order, $orderItem);
                            } else {
                                foreach ($orderItem as $orderItemItem) {
                                    $this->updateOrCreatePlatformMarketOrderItem($order, $orderItemItem);
                                }
                            }
                        }
                    }
                }
                //update order qty shipped && qty unshipped && total_amout
                $this->updateToConfirmSalesOrderByOrderItem($storeName, $order['purchaseid']);
            }

            return true;
        }
    }

    public function getOrder($storeName, $orderId)
    {
        $this->priceMinisterOrder = new PriceMinisterOrder($storeName);
        $this->storeCurrency = $this->priceMinisterOrder->getStoreCurrency();
        $this->priceMinisterOrder->setOrderId($orderId);

        return $this->priceMinisterOrder->fetchOrder();
    }

    public function confirmSalesOrder($storeName, $itemId)
    {
        $this->priceMinisterOrderList = new PriceMinisterOrderList($storeName);
        $this->priceMinisterOrderList->setConfirmItemId($itemId);
        $result = $this->priceMinisterOrderList->confirmSalesOrder();

        return $result;
    }

    public function getOrderList($storeName, $fileName = '')
    {
        $this->priceMinisterOrderList = new PriceMinisterOrderList($storeName);
        $this->storeCurrency = $this->priceMinisterOrderList->getStoreCurrency();
        if ($fileName) {
            $originOrderList = $this->getFileData($fileName);
            $originOrderList = unserialize($originOrderList);
        } else {
            $originOrderList = $this->priceMinisterOrderList->getNewSales();
            $this->saveDataToFile(serialize($originOrderList), 'getNewSales');
        }

        return $originOrderList;
    }

    public function getOrderItemList($order, $orderId)
    {
        $originOrderItemList = $order['items'];

        return $originOrderItemList;
    }

    public function submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList)
    {
        $storeName = $platformOrderIdList[$esgOrder->platform_order_id];
        $result = $this->checkOrderStatusToShip($storeName,$esgOrder->platform_order_id);
        if($result){
            $extItemCd = $esgOrder->soItem->pluck('ext_item_cd');
            $message = $result = "";
            foreach ($extItemCd as $extItem) {
                $itemIds = explode('||', $extItem);
                foreach ($itemIds as $itemId) {
                    if ($esgOrderShipment && $itemId) {
                        $courier = $this->getPriceMinisterCourier($esgOrderShipment->courierInfo);
                        if ($courier) {
                            $this->priceMinisterOrderTracking = new PriceMinisterOrderTracking($storeName);
                            $this->priceMinisterOrderTracking->setItemId($itemId);
                            $this->priceMinisterOrderTracking->setTransporterName($courier['transporter_name']);
                            $this->priceMinisterOrderTracking->setTrackingNumber($esgOrderShipment->tracking_no);
                            if (isset($courier['tracking_url'])) {
                                $this->priceMinisterOrderTracking->setTrackingUrl($courier['tracking_url']);
                            }
                            try {
                                $result = $this->priceMinisterOrderTracking->setTrackingPackageInfo();
                                $this->saveDataToFile($result, 'setTrackingPackageInfo');
                            } catch (Exception $e) {
                                $message .= "platform_order_id: ". $esgOrder->platform_order_id ."message: {$e->getMessage()}, Line: {$e->getLine()}\r\n";
                            }
                        }
                    }
                }
            }
            if ($message) {
                $header = "From: admin@eservicesgroup.com\r\n";
                $to = "jimmy.gao@eservicesgroup.com, brave.liu@eservicesgroup.com";
                $subject = "Alert, Submit PriceMinister Order Fufillment Shipment Failed";
                mail($to, $subject, $message, $header);

                return false;
            }
            return $result == 'OK' ? true : false;
        } 
    }

    public function getPriceMinisterCourier($courierInfo)
    {
        $pmCourierId = $courierInfo->pm_courier_id;
        $priceMinisterCourier = [];
        if ($pmCourierId) {
            switch ($pmCourierId) {
                case 'DHL':
                    $priceMinisterCourier = array('transporter_name' => 'DHL');
                    break;
                case 'DPD':
                    $priceMinisterCourier = array('transporter_name' => 'DPD');
                    break;
                case 'DPD-UK':
                    $priceMinisterCourier = array(
                        'transporter_name' => 'DPD',
                        'tracking_url' => 'https://www.deutschepost.de/sendung/simpleQueryResult.html',
                        );
                    break;
                case 'CHRONOPOST':
                    $priceMinisterCourier = array(
                        'transporter_name' => 'CHRONOPOST',
                        'tracking_url' => 'http://www.chronopost.fr/en',
                        );
                    break;
                case 'TNT':
                    $priceMinisterCourier = array(
                        'transporter_name' => 'TNT',
                        );
                    break;
                case 'Autre':
                    $priceMinisterCourier = array(
                        'transporter_name' => 'Autre',
                        'tracking_url' => 'https://www.rpxonline.com/',
                        );
                    break;

                default:
                    // code...
                    break;
            }
        }

        if ($priceMinisterCourier) {
            return $priceMinisterCourier;
        } else {

            $courierId = $courierInfo->courier_id;
            $courierName = $courierInfo->courier_name;
            if (!$pmCourierId) {
                $message = "courierId: $courierId, courierName: $courierName Lack aftership Id Mapping";
            } else {
                $message = "courierId: $courierId, courierName: $courierName Lack with Priceminister courier Mapping, Please Contact IT Support";
            }

            $to = 'priceministerfr@brandsconnect.net, celine@eservicesgroup.net';
            $header = "From: admin@eservicesgroup.com\r\n";
            $header .= "Cc: handy.hon@eservicesgroup.com, jimmy.gao@eservicesgroup.com, brave.liu@eservicesgroup.com\r\n";

            mail($to, "Alert, Courier: {$courierName} Lack Mapping", $message, $header);

            return false;
        }

    }

    // update or insert data to databse
    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName)
    {
        // default 1st order item status as order status
        if (isset($order['items']['item']['itemstatus'])) {
            $orderStatus = $order['items']['item']['itemstatus'];
        } else {
            $orderStatus = $order['items']['item'][0]['itemstatus'];
        }
        $y = (int) substr($order['purchasedate'], 6, 4);
        $m = (int) substr($order['purchasedate'], 3, 2);
        $d = (int) substr($order['purchasedate'], 0, 2);
        $h = (int) substr($order['purchasedate'], 11, 2);
        $i = (int) substr($order['purchasedate'], 14, 2);
        $purchasedate = date('Y-m-d H:i:s', mktime($h, $i, 0, $m, $d, $y));
        $platformStore = $this->getPlatformStore($storeName);
        $object = [
            'platform' => $storeName,
            'biz_type' => $this->getPlatformId(),
            'store_id' => $platformStore->id,
            'platform_order_id' => $order['purchaseid'],
            'platform_order_no' => $order['purchaseid'],
            'purchase_date' => $purchasedate,
            'last_update_date' => '0000-00-00 00:00:00',
            'order_status' => $orderStatus,
            'esg_order_status' => $this->getSoOrderStatus($orderStatus),
            'buyer_email' => $order['purchaseid'].'@priceminister-api.com',
            'buyer_name' => $order['deliveryinformation']['purchasebuyerlogin'],
            'currency' => $this->storeCurrency,
            'shipping_address_id' => $addressId,
        ];

        $platformMarketOrder = PlatformMarketOrder::updateOrCreate(
            ['platform_order_id' => $order['purchaseid']],
            $object
        );
    }

    // update or insert data to order item
    public function updateOrCreatePlatformMarketOrderItem($order, $orderItem)
    {
        $object = [
            'platform_order_id' => $order['purchaseid'],
            'seller_sku' => $orderItem['sku'],
            'order_item_id' => $orderItem['itemid'],
            'title' => $orderItem['headline'],
            'quantity_ordered' => 1,
        ];
        if (isset($orderItem['shipped'])) {
            $object['quantity_shipped'] = $orderItem['shipped'];
        }
        if (isset($orderItem['price']['amount'])) {
            $object['item_price'] = $orderItem['price']['amount'];
        }
        if (isset($orderItem['itemstatus'])) {
            $object['status'] = studly_case($orderItem['itemstatus']);
        }

        $platformMarketOrderItem = PlatformMarketOrderItem::updateOrCreate(
            [
                    'platform_order_id' => $order['purchaseid'],
                    'order_item_id' => $orderItem['itemid'],
                ],
            $object
        );

        return $platformMarketOrderItem;
    }

    // update or insert shipping address
    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName)
    {
        $object = [];
        $object['platform_order_id'] = $order['purchaseid'];
        $deliveryInfo = $order['deliveryinformation']['deliveryaddress'];
        $object['name'] = (string) $deliveryInfo['firstname'].' '.(string) $deliveryInfo['lastname'];
        $object['address_line_1'] = (string) $deliveryInfo['address1'];
        $object['address_line_2'] = (string) $deliveryInfo['address2'];
        $object['city'] = (string) $deliveryInfo['city'];
        $object['county'] = (string) $deliveryInfo['country'];
        $object['country_code'] = $this->getEsgCountryCode($deliveryInfo['countryalpha2']);
        $object['bill_country_code'] = $this->getEsgCountryCode($deliveryInfo['countryalpha2']);

        $object['postal_code'] = $deliveryInfo['zipcode'];
        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(
            ['platform_order_id' => $order['purchaseid']],
            $object
        );

        return $platformMarketShippingAddress->id;
    }

    public function updateToConfirmSalesOrderByOrderItem($storeName, $purchaseid)
    {
        $platformMarketOrder = PlatformMarketOrder::where('platform_order_id', $purchaseid)->first();
        $platformMarketOrderItemList = $platformMarketOrder->platformMarketOrderItem;
        $item_shipped = $item_unshiped = 0;
        $total_amount = 0.00;
        foreach ($platformMarketOrderItemList as $platformMarketOrderItem) {
            $quantity_shipped = $platformMarketOrderItem->quantity_shipped;
            $quantity_ordered = $platformMarketOrderItem->quantity_ordered;

            $item_shipped += $quantity_shipped;
            $item_unshiped += ($quantity_ordered - $quantity_shipped);

            $item_price = $platformMarketOrderItem->item_price;
            $total_amount += $item_price;

            $result = $this->confirmSalesOrder($storeName, $platformMarketOrderItem->order_item_id);
        }
        $platformMarketOrder->total_amount = $total_amount;
        $platformMarketOrder->total_amount = $total_amount;
        $platformMarketOrder->order_status = 'ACCEPTED';
        $platformMarketOrder->esg_order_status = $this->getSoOrderStatus('ACCEPTED');
        $platformMarketOrder->number_of_items_shipped = $item_shipped;
        $platformMarketOrder->number_of_items_unshipped = $item_unshiped;
        $platformMarketOrder->save();
    }

    public function getShipedOrderState()
    {
        return  "SHIPPED";
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
        switch ($platformOrderStatus) {
            case 'COMMITTED':
                $status = PlatformMarketConstService::ORDER_STATUS_PAID;
                break;
            case 'PENDING':
                $status = PlatformMarketConstService::ORDER_STATUS_PENDING;
                break;
            case 'ACCEPTED':
            case 'ON_HOLD':
                $status = PlatformMarketConstService::ORDER_STATUS_UNSHIPPED;
                break;
            case 'CANCELLED':
                $status = PlatformMarketConstService::ORDER_STATUS_CANCEL;
                break;
            default:
                $status = 1;
                break;
        }

        return $status;
    }

    public function getFileData($fileName)
    {
        $filePath = storage_path().'/app/ApiPlatform/'.$this->getPlatformId().'/getOrderList/'.date('Y');
        $file = $filePath.'/'.$fileName;
        $data = file_get_contents($file);

        return $data;
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

    public function updatePlatMarketOrderStatus($storeName,$platformMarketOrderList)
    {
        $this->priceMinisterOrderInfo = new PriceMinisterOrderInfo($storeName);
        foreach ($platformMarketOrderList as $platformMarketOrder) {
            $this->priceMinisterOrderInfo->setPurchaseId($platformMarketOrder->platform_order_id);
            $orderInfo = $this->priceMinisterOrderInfo->getBillingInformation();
            if(!empty($orderInfo)){
                foreach ($orderInfo as $key => $orderItem) {
                    if (isset($orderItem['item']['itemstatus'])) {
                        $orderStatus = $orderItem['item']['itemstatus'];
                    } else {
                        $orderStatus = $orderItem['item'][0]['itemstatus'];
                    }
                }
                $soOrderStatus = $this->getSoOrderStatus($orderStatus);
                if($platformMarketOrder->esg_order_status != $soOrderStatus){
                    $platformMarketOrder->order_status = $orderStatus;
                    $platformMarketOrder->esg_order_status = $soOrderStatus;
                    $platformMarketOrder->save();
                }
            }
        } 
    }

    private function checkOrderStatusToShip($storeName,$orderId)
    {
        $message = "";
        try {
            $this->priceMinisterOrderInfo = new PriceMinisterOrderInfo($storeName);
            $this->priceMinisterOrderInfo->setPurchaseId($orderId);
            $orderInfo = $this->priceMinisterOrderInfo->getBillingInformation();
            if(!empty($orderInfo)){
                foreach ($orderInfo as $key => $orderItem) {
                    if (isset($orderItem['item']['shipped'])) {
                        $orderStatus = $orderItem['item']['itemstatus'];
                        $shipped = $orderItem['item']['shipped'];
                    } else {
                        $orderStatus = $orderItem['item'][0]['itemstatus'];
                        $shipped = $orderItem['item'][0]['shipped'];
                    }
                }
                if($shipped){
                   $object = array(
                        "order_status" => "Shipped",
                        "esg_order_status"=> PlatformMarketConstService::ORDER_STATUS_SHIPPED,
                    );
                    PlatformMarketOrder::where('platform_order_id', '=', $orderId)->update($object);
                    return false;
                }else{
                    $platformMarketOrder = PlatformMarketOrder::where('platform_order_id', '=', $orderId)->first();
                    if($platformMarketOrder->order_status != $orderStatus){
                        $platformMarketOrder->order_status = $orderStatus;
                        $platformMarketOrder->esg_order_status = $this->getSoOrderStatus($orderStatus);
                        $platformMarketOrder->save();
                    }
                    $acceptedStatus = array("COMMITTED","PENDING","ACCEPTED","ON_HOLD");
                    if(in_array($orderStatus,$acceptedStatus)){
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        mail('jimmy.gao@eservicesgroup.com', $storeName.' checkOrderStatusToShip for platformOrderID: '.$orderId, $message);
        return false;
    }
}
