<?php

namespace App\Repository\Qoo10Mws;

class Qoo10ProductUpdate extends Qoo10ProductCore
{
    private $itemPrice = '';
    private $itemQty = '';
    private $expireDate = '';

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function setGoodsPrice()
    {
        return parent::query('setGoodsPrice', 'GET', $this->getRequestParams());
    }

    public function setGoodsInventory()
    {
        return parent::query('setGoodsInventory', 'GET', $this->getRequestParams());
    }

    protected function getRequestParams()
    {
        if ($this->getItemCode()) {
            $requestParams['ItemCode'] = $this->getItemCode();
        }

        $requestParams['SellerCode'] = $this->getSellerCode();

        if ($this->getItemPrice() != '') {
            $requestParams['ItemPrice'] = $this->getItemPrice();
        }

        if ($this->getItemQty() != '') {
            $requestParams['ItemQty'] = $this->getItemQty();
        }

        $requestParams['ExpireDate'] = $this->getExpireDate();

        return $requestParams;
    }

    public function getItemPrice()
    {
        return $this->itemPrice;
    }

    public function setItemPrice($value)
    {
        $this->itemPrice = $value;
    }

    public function getItemQty()
    {
        return $this->itemQty;
    }

    public function setItemQty($value)
    {
        $this->itemQty = $value;
    }

    public function getExpireDate()
    {
        return $this->expireDate;
    }

    public function setExpireDate($value)
    {
        $this->expireDate = $value;
    }
}