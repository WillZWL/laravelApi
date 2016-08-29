<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterOrderTracking extends PriceMinisterOrderCore
{
    private $version = '2016-03-16';
    private $itemId;
    private $transporterName;
    private $trackingNumber;
    private $trackingUrl;
    private $_requestParams;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
    }

    public function setTrackingPackageInfo()
    {
        $this->_requestParams = parent::initRequestParams();
        $this->_requestParams['action'] = 'settrackingpackageinfos';
        $this->_requestParams['version'] = '2016-03-16';
        $this->_requestParams['itemid'] = $this->getItemId();
        $this->_requestParams['transporter_name'] = $this->getTransporterName();
        $this->_requestParams['tracking_number'] = $this->getTrackingNumber();
        $this->_requestParams['tracking_url'] = $this->getTrackingUrl();

        return parent::query($this->_requestParams);
    }

    protected function prepare($data = array())
    {
        if (isset($data['response']) && isset($data['response']['status'])) {
            return parent::fix($data['response']['status']);
        }

        return null;
    }

    public function setUrlBase()
    {
        $url = $this->urlbase.'sales_ws';
        $this->urlbase = $url;
    }

    public function setItemId($value)
    {
        $this->itemId = $value;
    }

    public function getItemId()
    {
        return  $this->itemId;
    }

    public function setTransporterName($value)
    {
        $this->transporterName = $value;
    }

    public function getTransporterName()
    {
        return  $this->transporterName;
    }

    public function setTrackingNumber($value)
    {
        $this->trackingNumber = $value;
    }

    public function getTrackingNumber()
    {
        return  $this->trackingNumber;
    }

    public function setTrackingUrl($value)
    {
        $this->trackingUrl = $value;
    }

    public function getTrackingUrl()
    {
        return $this->trackingUrl;
    }
}
