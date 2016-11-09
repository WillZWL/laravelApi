<?php

namespace App\Repository\Qoo10Mws;

class Qoo10OrderUpdate extends Qoo10OrderCore
{
    private $shippingCorp;
    private $trackingNo;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function setSendingInfo()
    {
        return parent::query('setSendingInfo', 'GET', $this->getRequestParams());
    }

    protected function getRequestParams()
    {
        if ($this->getOrderNo()) {
            $requestParams['OrderNo'] = $this->getOrderNo();
        }

        if ($this->getShippingCorp()) {
            $requestParams['ShippingCorp'] = $this->getShippingCorp();
        }

        if ($this->getTrackingNo()) {
            $requestParams['TrackingNo'] = $this->getTrackingNo();
        }

        return $requestParams;
    }

    public function getShippingCorp()
    {
        return $this->shippingCorp;
    }

    public function setShippingCorp($value)
    {
        $this->shippingCorp = $value;
    }

    public function getTrackingNo()
    {
        return $this->trackingNo;
    }

    public function setTrackingNo($value)
    {
        $this->trackingNo = $value;
    }

}