<?php

namespace App\Repository\Qoo10Mws;

class Qoo10Order extends Qoo10OrderCore
{
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchOrder()
    {
        return parent::query('getShippingAndClaimInfoByOrderNo', 'GET', $this->getRequestParams());
    }

    protected function getRequestParams()
    {
        if ($this->getOrderNo()) {
            $requestParams['OrderNo'] = $this->getOrderNo();
        }

        return $requestParams;
    }

}