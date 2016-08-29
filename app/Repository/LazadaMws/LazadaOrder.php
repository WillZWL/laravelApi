<?php

namespace App\Repository\LazadaMws;

class LazadaOrder extends LazadaOrderCore
{
    private $action;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchOrder()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetOrder';
        if ($this->getOrderId() && intval($this->getOrderId()) > 0) {
            $requestParams['OrderId'] = $this->getOrderId();
        }

        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['Orders']) && isset($data['Body']['Orders']['Order'])) {
            return $data['Body']['Orders']['Order'];
        }

        return null;
    }
}
