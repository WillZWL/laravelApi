<?php

namespace App\Repository\NeweggMws;

class NeweggOrderCore extends NeweggCore
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
