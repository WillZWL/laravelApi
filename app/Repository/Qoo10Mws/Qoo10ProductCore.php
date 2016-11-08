<?php

namespace App\Repository\Qoo10Mws;

class Qoo10ProductCore extends Qoo10Core
{
    protected $sku;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getSku()
    {
        return $this->sku;
    }

    public function setSku($value)
    {
        $this->sku = $value;
    }

}