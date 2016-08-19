<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterOrder extends PriceMinisterOrderCore
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
        $requestParams['action'] = 'getcurrentsales';
        return $requestParams;
    }

    protected function prepare($data = [])
    {
        if (isset($data['response']) && isset($data['response']['sales'])) {
            return $data['response']['sales'];
        }
        return null;
    }
}