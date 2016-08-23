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
use App\Repository\TangaMws\TangaOrderItemList;

class ApiTangaService extends ApiBaseService implements ApiPlatformInterface
{

    function __construct()
    {
        //
    }

    public function getPlatformId()
    {
        return 'Tanga';
    }

    public function retrieveOrder($storeName)
    {
        $orginOrderList = $this->getOrderList($storeName);
        # Now no any order, Temporarily cannot take data, No clearly tanga order item fotmat, go on deal with tanga after wait a new order
        if ($orginOrderList) {
            foreach ($orginOrderList as $order) {
                # code...
            }

            return true;
        }
    }

    public function getOrderList($storeName)
    {
        $this->tangaOrderList=new TangaOrderList($storeName);
        // $this->storeCurrency=$this->tangaOrderList->getStoreCurrency();
        $lastTime=date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
        $this->tangaOrderList->setStartAt($lastTime);
        $originOrderList=$this->tangaOrderList->fetchOrderList();
        $this->saveDataToFile(serialize($originOrderList),"getOrderList");
        return $originOrderList;
    }

    public function getOrderItemList($storeName,$orderId)
    {
    }

    public function submitOrderFufillment($esgOrder,$esgOrderShipment,$platformOrderIdList)
    {
    }

    //update or insert data to database
    public function updateOrCreatePlatformMarketOrder($order,$addressId,$storeName)
    {
    }

    public function updateOrCreatePlatformMarketOrderItem($order,$orderItem)
    {
    }

    public function updateOrCreatePlatformMarketShippingAddress($order,$storeName)
    {
    }

    public function getSoOrderStatus($platformOrderStatus)
    {
    }
}