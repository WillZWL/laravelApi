<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterProductsCore extends PriceMinisterCore
{
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getProductTypes()
    {   
        return parent::query($this->getProductTypesRequestParams());
    }

    public function getProductTypesRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['action'] = 'producttypes';
        $requestParams['version'] = '2011-11-29';
        return  $requestParams;
    }
}