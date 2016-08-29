<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterOrderCore extends PriceMinisterCore
{
    private $orderId;

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
}
