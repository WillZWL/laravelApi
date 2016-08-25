<?php

namespace App\Repository\FnacMws;

class FnacOrderCore extends FnacCore
{

    private $orderId;
    protected $requestXml;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setOrderId($value)
    {
        $this->orderId = $value;
    }

    public function setOrdersQueryPath()
    {
        $this->fnacPath = "api.php/orders_query";
    }

    public function setOrdersUpdatePath()
    {
        $this->fnacPath = "api.php/orders_update";
    }

    protected function getRequestXml()
    {
        return $this->requestXml;
    }
}