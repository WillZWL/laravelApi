<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterOrderList extends PriceMinisterOrderCore
{
    private $version = '2016-03-16';
    private $updatedAfter;

    public function __construct($store)
    {
      parent::__construct($store);
      $this->setUrlBase();
    }

    public function fetchOrderList()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams["action"] = "getcurrentsales";
        $requestParams['version'] = $this->version;
        $requestParams['purchasedate'] = $this->getUpdatedAfter();
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data["response"]) && isset($data["response"]["sales"]) && isset($data["response"]["sales"]["sale"])) {
            return parent::fix($data["response"]["sales"]["sale"]);
        }
        return null;
    }

    public function setUrlBase()
    {
        $url = $this->urlbase."sales_ws";
        $this->urlbase = $url;
    }

    public function getUpdatedAfter()
    {
        return $this->updatedAfter;
    }

    public function setUpdatedAfter($value)
    {
        $this->updatedAfter=$value;
    }
}