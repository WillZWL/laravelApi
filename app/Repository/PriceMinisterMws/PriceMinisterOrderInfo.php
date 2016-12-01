<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterOrderInfo extends PriceMinisterOrderCore
{
    private $version = '2016-03-16';
    private $_requestParams;
    private $purchaseId;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
        $this->getRequestParams();
    }

    public function getBillingInformation()
    {
        $this->_requestParams['action'] = 'getbillinginformation';
        $this->_requestParams['version'] = '2016-03-16';
        $this->_requestParams['purchaseid'] = $this->getPurchaseId();

        return parent::query($this->_requestParams);
    }

    public function getShippingInformation()
    {
        $this->_requestParams['action'] = 'getshippinginformation';
        $this->_requestParams['version'] = '2014-02-11';
        $this->_requestParams['purchaseid'] = $this->getPurchaseId();

        return parent::query($this->_requestParams);
    }

    protected function getRequestParams()
    {
        $this->_requestParams = parent::initRequestParams();
    }

    protected function prepare($data = array())
    {
        if (isset($data['response']) && isset($data['response']['billinginformation']) && isset($data['response']['billinginformation']['items'])) {
            return parent::fix($data['response']['billinginformation']['items']);
        }else if(isset($data['response']) && isset($data['response']['shippinginformation'])) {
            return $data['response']['shippinginformation'];
        }
        return null;
    }

    public function setUrlBase()
    {
        $url = $this->urlbase.'sales_ws';
        $this->urlbase = $url;
    }

    public function setPurchaseId($value)
    {
        $this->purchaseId = $value;
    }

    public function getPurchaseId()
    {
        return  $this->purchaseId;
    }
    
}
