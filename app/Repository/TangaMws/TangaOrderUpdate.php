<?php

namespace App\Repository\TangaMws;

class TangaOrderUpdate extends TangaOrderCore
{
    private $orderId;
    private $carrier;
    private $trackingNumber;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->settangaPath();
    }

    public function updateTrackingNumber($requestData)
    {

        return $this->curlPostData($requestData);
    }

    public function getRequestTrackingData()
    {
        $requestData = [];
        $requestData['package_id'] = $this->getOrderId();
        $requestData['carrier'] = $this->getCarrier();
        $requestData['tracking_number'] = $this->getTrackingNumber();

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

    public function getCarrier()
    {
        return $this->carrier;
    }

    public function setCarrier($carrier)
    {
        $this->carrier = $carrier;
    }
}
