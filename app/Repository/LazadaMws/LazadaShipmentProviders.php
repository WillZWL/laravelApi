<?php

namespace App\Repository\LazadaMws;

class LazadaShipmentProviders extends LazadaOrderCore
{
    private $action;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchShipmentProviders()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetShipmentProviders';

        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['ShipmentProviders']) && isset($data['Body']['ShipmentProviders']['ShipmentProvider'])) {
            return parent::fix($data['Body']['ShipmentProviders']['ShipmentProvider']);
        }

        return null;
    }
}
