<?php

namespace App\Services;

//use SplEnum;

abstract class PlatformMarketConstService
{
    //order status
    const ORDER_STATUS_CANCEL = 0;
    const ORDER_STATUS_NEW = 1;
    const ORDER_STATUS_PAID = 2;
    const ORDER_STATUS_FULFILMENT_CHECKED = 3;
    const ORDER_STATUS_READYTOSHIP = 5;
    const ORDER_STATUS_SHIPPED = 6;
    const ORDER_STATUS_RETURENED = 7;
    const ORDER_STATUS_RETURENED_REJECTED = 8;
    const ORDER_STATUS_DELIVERED = 9;
    const ORDER_STATUS_COMPLETE = 10;
    const ORDER_STATUS_PENDING = 13; //not paid order
    const ORDER_STATUS_UNSHIPPED = 14;
    const ORDER_STATUS_FAIL = 15;
    const ORDER_STATUS_UNCONFIRMED = 16;
    //order status end

    //update price and inventory by binary
    const PENDING_PRICE = 2; //10
    const COMPLETE_PRICE = 8; //1000
    const PENDING_INVENTORY = 4;//100
    const COMPLETE_INVENTORY = 16;//10000
    const PENDING_PRODUCT = 32;//100000
    const COMPLETE_PRODUCT = 64;//1000000


}
