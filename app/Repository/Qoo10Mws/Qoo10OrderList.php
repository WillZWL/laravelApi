<?php

namespace App\Repository\Qoo10Mws;

class Qoo10OrderList extends Qoo10OrderCore
{
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchOrderList()
    {
        return parent::query('getShippingInfo', 'GET', $this->getRequestParams());
    }

    protected function getRequestParams()
    {
        if ($this->getShippingStat('CheckOrder')) {
            $requestParams['ShippingStat'] = $this->getShippingStat('CheckOrder');
        }

        if ($this->getStartDate()) {
            $requestParams['Search_Sdate'] = $this->getStartDate();
        }

        if ($this->getEndDate()) {
            $requestParams['Search_Edate'] = $this->getEndDate();
        }

        return $requestParams;
    }

}