<?php

namespace App\Contracts;

interface ApiPlatformProductInterface
{
    public function getPlatformId();
    /******************************************
    **  function getPlatformId
    **  this will return Store Name
    ********************************************/

    public function getProductList($storeName);
    /******************************************
    **  function getProductList
    **  this will return Marketplace product list
    ********************************************/

    public function submitProductPriceAndInventory($storeName);
    /******************************************
    **  function submitProductPriceAndInventory
    **  this will change Marketplace product price and inventory
    ********************************************/

    public function submitProductCreate($storeName,$productGroup);
    /******************************************
    **  function submitProductCreate
    **  this will create Marketplace Product
    ********************************************/
}
