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
use App\Models\PlatformMarketAuthorization;

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

    public function initWishClient($storeName)
    {   
        if(isset($this->stores[$storeName])){
            $marketPlaceToken = PlatformMarketAuthorization::MarketPlaceToken($storeName)->first();
            $currentDate = strtotime(date("y-m-d"));
            $expireDate = strtotime($marketPlaceToken->expire_date);
            if($expireDate - $currentDate <= 1){
                $accessToken = $this->refreshWishToken($storeName,$marketPlaceToken);
            }else{
                $accessToken = $marketPlaceToken->access_token;
            }
            $this->wishClient = new WishClient($accessToken,'prod');
            $products = $this->wishClient->getAllProducts();
            print_r($products);exit();
        }else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function getTokenByAuthorizationCode($storeName,$authorizationCode,$url)
    {
        if(isset($this->stores[$storeName])){
            $auth = new WishAuth($this->stores[$storeName]['client_id'],$this->stores[$storeName]['client_secret'],'prod');
            $response = $auth->getToken($authorizationCode,$url);
            $accessToken = $response->getData()->access_token;
            $refreshToken = $response->getData()->refresh_token;
        }else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function refreshWishToken($storeName,$marketPlaceToken)
    {
        if(isset($this->stores[$storeName])){
            $auth = new WishAuth($this->stores[$storeName]['client_id'],$this->stores[$storeName]['client_secret'],'prod');
            $response = $auth->refreshToken($marketPlaceToken->refresh_token);
            if($accessToken = $response->getData()->access_token){
                $marketPlaceToken->access_token = $accessToken;
                $marketPlaceToken->expire_date = date('Y-m-d',strtotime('+29 day'));
                $marketPlaceToken->save();
                return $accessToken;
            }
        }else {
            throw new Exception('Config file does not exist or cannot be read!');
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
