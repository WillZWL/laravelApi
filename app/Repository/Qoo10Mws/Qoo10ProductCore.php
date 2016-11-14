<?php

namespace App\Repository\Qoo10Mws;

class Qoo10ProductCore extends Qoo10Core
{
    protected $itemCode = '';
    protected $sellerCode = '';

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getItemCode()
    {
        return $this->itemCode;
    }

    public function setItemCode($value)
    {
        $this->itemCode = $value;
    }

    public function getSellerCode()
    {
        return $this->sellerCode;
    }

    public function setSellerCode($value)
    {
        $this->sellerCode = $value;
    }

}