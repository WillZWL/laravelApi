<?php

namespace App\Repository\NeweggMws;

class NeweggProductCore extends NeweggCore
{
    private $productId;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function setProductId($value)
    {
        $this->productId = $value;
    }

}
