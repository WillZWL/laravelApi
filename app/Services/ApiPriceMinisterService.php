<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;
use App\Models\Schedule;
use Illuminate\Support\Arr;
//use priceminister api package
use App\Repository\PriceMinisterMws\PriceMinisterOrder;
use App\Repository\PriceMinisterMws\PriceMinisterOrderList;
use App\Repository\PriceMinisterMws\PriceMinisterOrderItemList;
use App\Repository\PriceMinisterMws\PriceMinisterOrderTracking;


class ApiPriceMinisterService extends ApiBaseService implements ApiPlatformInterface
{
    private $storeCurrency;

    public function __construct()
    {

    }

    public function getPlatformId()
    {
        return 'PriceMinister';
    }

    public function retrieveOrder($storeName)
    {
        // $originOrderList = $this->getOrderList($storeName, "2016-08-17-06-07.txt");
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
                $this->updateToConfirmSalesOrderByOrderItem($storeName,$order['purchaseid']);
            }
            return true;
        }
    }

    public function getOrder($storeName, $orderId)
    {
        $this->priceMinisterOrder = new PriceMinisterOrder($storeName);
        $this->storeCurrency = $this->priceMinisterOrder->getStoreCurrency();
        $thhis->PriceMinisterOrder->setOrderId($orderId);

        return $this->priceMinisterOrder->fetchOrder();
    }

    public function confirmSalesOrder($storeName,$itemId)
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
            $this->saveDataToFile(serialize($originOrderList), "getNewSales");
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
        $storeName=$platformOrderIdList[$esgOrder->platform_order_id];
        $itemIds=$esgOrder->soItem->pluck("ext_item_cd");
        foreach($itemIds as $itemId){
            if ($esgOrderShipment) {
                $courier=$this->getPriceMinisterCourier($esgOrderShipment->courierInfo->aftership_id);
                $this->priceMinisterOrderTracking=new PriceMinisterOrderTracking($storeName);
                $this->priceMinisterOrderTracking->setItemId($itemId);
                $this->priceMinisterOrderTracking->setTransporterName($courier["transporter_name"]);
                $this->priceMinisterOrderTracking->setTrackingNumber($esgOrderShipment->tracking_no);
                if(isset($courier["tracking_url"]))
                $this->priceMinisterOrderTracking->setTrackingUrl($courier["tracking_url"]);
                //print_r($this->priceMinisterOrderTracking);exit();
                $result=$this->priceMinisterOrderTracking->setTrackingPackageInfo();
            }
        }
        return $result == "OK" ? true :false;
    }

    public function getPriceMinisterCourier($courier)
    {
        switch ($courier) {
            case 'dhl':
            case 'dhl-global-mail':
                $priceMinisterCourier=array("transporter_name" => "DHL");
                break;
            case 'dpd':
                $priceMinisterCourier=array("transporter_name" => 'DPD');
                break;
            case 'dpd-uk':
                $priceMinisterCourier=array(
                    "transporter_name" => 'DPD',
                    "tracking_url"=>'https://www.deutschepost.de/sendung/simpleQueryResult.html'
                    );
                break;
            case 'chronopost-france':
                $priceMinisterCourier=array(
                    "transporter_name"=>'CHRONOPOST',
                    "tracking_url"=>'http://www.chronopost.fr/en'
                    );
                break;
            case 'tnt':
                $priceMinisterCourier=array(
                    "transporter_name"=>'TNT'
                    );
                break;
                
            default:
                # code...
                break;
        }
        return $priceMinisterCourier;
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
        $object = [
            'platform' => $storeName,
            'biz_type' => $this->getPlatformId(),
            'platform_order_id' => $order['purchaseid'],
            'platform_order_no' => $order['purchaseid'],
            'purchase_date' => $purchasedate,
            'last_update_date' => '0000-00-00 00:00:00',
            'order_status' => $orderStatus,
            'esg_order_status' => $this->getSoOrderStatus($orderStatus),
            'buyer_email' => $order['purchaseid']."@priceminister-api.com",
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
        $object['name'] = (string)$deliveryInfo['firstname']." ".(string)$deliveryInfo['lastname'];
        $object['address_line_1'] = (string)$deliveryInfo['address1'];
        $object['address_line_2'] = (string)$deliveryInfo['address2'];
        $object['city'] = (string)$deliveryInfo['city'];
        $object['county'] = (string)$deliveryInfo['country'];
        $object['country_code'] = $this->getEsgCountryCode($deliveryInfo['countryalpha2']);
        $object['bill_country_code'] = $this->getEsgCountryCode($deliveryInfo['countryalpha2']);

        $object['postal_code'] = $deliveryInfo['zipcode'];
        $platformMarketShippingAddress = PlatformMarketShippingAddress::updateOrCreate(
            ['platform_order_id' => $order['purchaseid']],
            $object
            );
        return $platformMarketShippingAddress->id;
    }

    public function updateToConfirmSalesOrderByOrderItem($storeName,$purchaseid)
    {
        $platformMarketOrder = PlatformMarketOrder::where('platform_order_id', $purchaseid)->first();
        $platformMarketOrderItemList = $platformMarketOrder->platformMarketOrderItem;
        $item_shipped = $item_unshiped = 0;
        $total_amount = 0.00;
        foreach ($platformMarketOrderItemList as $platformMarketOrderItem) {

            $quantity_shipped = $platformMarketOrderItem->quantity_shipped;
            $quantity_ordered = $platformMarketOrderItem->quantity_ordered;

            $item_shipped += $quantity_shipped;
            $item_unshiped += ($quantity_ordered-$quantity_shipped);

            $item_price = $platformMarketOrderItem->item_price;
            $total_amount += $item_price;

            $result=$this->confirmSalesOrder($storeName,$platformMarketOrderItem->order_item_id);
        }
        $platformMarketOrder->total_amount = $total_amount;
        $platformMarketOrder->total_amount = $total_amount;
        $platformMarketOrder->order_status = "ACCEPTED";
        $platformMarketOrder->esg_order_status =$this->getSoOrderStatus("ACCEPTED");
        $platformMarketOrder->number_of_items_shipped = $item_shipped;
        $platformMarketOrder->number_of_items_unshipped = $item_unshiped;
        $platformMarketOrder->save();
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
        switch ($platformOrderStatus) {
            case 'COMMITTED':
                $status = PlatformOrderService::ORDER_STATUS_PAID;
                break;
            case 'PENDING':
                $status = PlatformOrderService::ORDER_STATUS_PENDING;
                break;
            case 'ACCEPTED':
            case 'ON_HOLD':
                $status = PlatformOrderService::ORDER_STATUS_UNSHIPPED;
                break;
            case 'CANCELLED':
                $status = PlatformOrderService::ORDER_STATUS_CANCEL;
                break;
            default:
                $status = 1;
                break;
        }
        return $status;
    }

    public function getFileData($fileName)
    {
        $filePath=storage_path()."/app/ApiPlatform/".$this->getPlatformId()."/getOrderList/" . date("Y");
        $file = $filePath."/".$fileName;
        $data = file_get_contents($file);
        return $data;
    }

    public function getEsgCountryCode($countryalpha2)
    {
        $countryCode=array(
            "FX" => "FR",
            "DE" => "DE"
         );
        if(isset($countryCode[$countryalpha2])){
            return $countryCode[$countryalpha2];
        }else{
            return $countryalpha2;
        }
    }
}