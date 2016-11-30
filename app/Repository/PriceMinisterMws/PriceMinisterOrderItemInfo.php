<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterOrderItemInfo extends PriceMinisterOrderCore
{
    private $version = '2016-03-16';
    private $_requestParams;
    private $itemId;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
        $this->getRequestParams();
    }

    public function getItemInfos()
    {
        $this->_requestParams['action'] = 'getbillinginformation';
        $this->_requestParams['version'] = '2016-03-16';
        $this->_requestParams['itemid'] = $this->getItemId();

        return parent::query($this->_requestParams);
    }

    protected function getRequestParams()
    {
        $this->_requestParams = parent::initRequestParams();
    }

    protected function prepare($data = array())
    {
        if (isset($data['response']) && isset($data['response']['item'])) {
            return parent::fix($data['response']['item']);
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
}
