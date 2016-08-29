<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use Config;

//use Wish api SDK
use Wish\WishAuth;
use Wish\WishClient;
use Wish\Model\WishTracker;
use Wish\Exception\OrderAlreadyFulfilledException;
use Wish\Model\WishReason;


class ApiWishService extends ApiBaseService implements ApiPlatformInterface
{
    protected $mwsName = 'wish-mws';
    protected $stores;

    public function __construct()
    {
        $this->stores = Config::get($this->mwsName.'.store');
    }

    public function getPlatformId()
    {
        return 'Wish';
    }

    public function initWishClient($storeName)
    {
        if(isset($this->stores[$storeName])){
            /*$auth = new WishAuth($this->stores[$storeName]['client_id'],$this->stores[$storeName]['client_secret'],'prod');
            $response = $auth->getToken($this->stores[$storeName]['auth_code'],'https://vanguard.dev');
            
            $accessToken = $response->getData()->access_token;
            $refreshToken = $response->getData()->refresh_token;
            print_r($accessToken);exit();*/
            $accessToken="02356b2a326f41a88fc5efb4518e196e";
            $this->wishClient = new WishClient($accessToken,'prod');
            $products = $this->wishClient->getAllProducts();
        }else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function retrieveOrder($storeName)
    {
        $orginOrderList = $this->getOrderList($storeName);
        // Now no any order, Temporarily cannot take data, No clearly tanga order item fotmat, go on deal with tanga after wait a new order
        if ($orginOrderList) {
            foreach ($orginOrderList as $order) {
                print_r($orginOrderList);exit();
            }

            return true;
        }
    }

    public function getOrderList($storeName)
    {
       /* 
        $this->saveDataToFile(serialize($originOrderList), 'getOrderList');*/
        $this->initWishClient($storeName);
        $dateTime=date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
        $originOrderList = $this->wishClient->getAllChangedOrdersSince($dateTime);
        return $originOrderList;
    }

    public function getOrderItemList($storeName, $orderId)
    {

    }

    public function submitOrderFufillment($esgOrder, $esgOrderShipment, $platformOrderIdList)
    {   

        return false;//testing
        $storeName = $platformOrderIdList[$esgOrder->platform_order_id];
        //$shipmentProviders = $this->getShipmentProviders($storeName);
        $countryCode = strtoupper(substr($storeName, -2));
        $this->initWishClient($storeName);
        $orderItemIds = array();
        $extItemCd = $esgOrder->soItem->pluck("ext_item_cd");
        foreach($extItemCd as $extItem){
            $itemIds = explode("||",$extItem);
            foreach($itemIds as $itemId){
                if ($esgOrderShipment && $itemId) {
                     $courier = $this->getWishCourier($esgOrderShipment->courierInfo->aftership_id);
                    $tracker = new WishTracker($courier,$esgOrderShipment->tracking_no,'Thanks for buying!');
                    $this->client->updateTrackingInfoById('53785043482e680c58a08f53',$tracker);
                }
            }
        }
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName)
    {
    }

    public function updateOrCreatePlatformMarketOrderItem($order, $orderItem)
    {
    }

    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName)
    {
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
    }
}
