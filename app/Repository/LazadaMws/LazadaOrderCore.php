<?php

namespace App\Repository\LazadaMws;

class LazadaOrderCore extends LazadaCore
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
