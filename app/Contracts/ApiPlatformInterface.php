<?php

namespace App\Contracts;

interface ApiPlatformInterface
{
    public function getPlatformId();
    /******************************************
    **  function getPlatformId
    **  this will return Store Name
    ********************************************/

    public function retrieveOrder($storeName,$schedule);
    /******************************************
    **  function retrieveOrder
    **  this will return get order and order items
    ********************************************/

    public function getOrderList($storeName);
    /******************************************
    **  function getOrderList
    **  this will return order list
    ********************************************/

    public function updateOrCreatePlatformMarketOrder($order, $addressId, $storeName);
    /******************************************
    **  function updateOrCreatePlatformMarketOrder
    **  this will return order items
    ********************************************/

    public function updateOrCreatePlatformMarketOrderItem($platformMarketOrderId, $order, $orderItem, $storeName);
    /******************************************
    **  function updateOrCreatePlatformMarketOrderItem
    **  this will update or create order item
    ********************************************/

    public function updateOrCreatePlatformMarketShippingAddress($order, $storeName);
    /******************************************
    **  function updateOrCreatePlatformMarketShippingAddress
    **  this will update or create order shippingAddress
    ********************************************/

    public function getSoOrderStatus($platformOrderStatus);
    /******************************************
    **  function getSoOrderStatus
    **  this will set atom_esg order so status
    ********************************************/
}
