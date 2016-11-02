<?php

namespace App\Repository\LazadaMws;

class LazadaFailureReasons extends LazadaOrderCore
{
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchFailureReasons()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetFailureReasons';

        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['Reasons']) && isset($data['Body']['Reasons']['Reason'])) {
            return parent::fix($data['Body']['Reasons']['Reason']);
        }
        return null;
    }

}
