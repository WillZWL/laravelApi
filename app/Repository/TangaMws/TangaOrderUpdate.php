<?php

namespace App\Repository\TangaMws;

class TangaOrderUpdate extends TangaOrderCore
{
    private $orderId;
    private $trackingNumber;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->settangaPath();
    }

    public function updateTrackingNumber()
    {

        return $this->curlPostData($this->getRequestData());
    }

    protected function getRequestData()
    {
        $requestData = "tracking_number=".$this->getTrackingNumber()."&order_id=".$this->getOrderId();

        return $requestData;
    }

    public function setTangaPath()
    {
        $this->tangaPath = '/api/vendors/'. $this->vendorAppId .'/set_tracking';
    }


    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }
}
