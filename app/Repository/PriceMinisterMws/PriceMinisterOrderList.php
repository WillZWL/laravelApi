<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterOrderList extends PriceMinisterOrderCore
{
    private $version = '2016-03-16';
    private $purchaseDate;
    private $confirmItemId;
    private $_requestParams;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
        $this->getRequestParams();
    }

    public function getNewSales()
    {
        $this->_requestParams["action"] = "getnewsales";
        $this->_requestParams['version'] = '2010-09-20';
        return parent::query($this->_requestParams);
    }

    public function confirmSalesOrder()
    {
        $this->_requestParams["action"] = "acceptsale";
        $this->_requestParams['version'] = '2010-09-20';
        $this->_requestParams['itemid'] = $this->getConfirmItemId();
        return parent::query($this->_requestParams);
    }

    public function getCurrentSales()
    {
        $this->_requestParams["action"] = "getcurrentsales";
        $this->_requestParams['version'] = '2016-03-16';
        $this->_requestParams['purchasedate'] = $this->getPurchaseDate();
        return parent::query($this->_requestParams);
    }

    protected function getRequestParams()
    {
        $this->_requestParams = parent::initRequestParams();
    }

    protected function prepare($data = array())
    {
        if (isset($data["response"]) && isset($data["response"]["sales"]) && isset($data["response"]["sales"]["sale"])) {
            return parent::fix($data["response"]["sales"]["sale"]);
        }else if(isset($data["response"]) && isset($data["response"]["status"])){
            return parent::fix($data["response"]["status"]);
        }
        return null;
    }

    public function setUrlBase()
    {
        $url = $this->urlbase."sales_ws";
        $this->urlbase = $url;
    }

    public function setConfirmItemId($value)
    {
        $this->confirmItemId = $value;
    }

    public function getConfirmItemId()
    {
        return  $this->confirmItemId;
    }

    public function setPurchaseDate($value)
    {
        $this->purchaseDate = $value;
    }

    public function getPurchaseDate()
    {
        return  $this->purchaseDate;
    }
}