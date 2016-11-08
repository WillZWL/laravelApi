<?php

namespace App\Repository\Qoo10Mws;

class Qoo10Product extends Qoo10ProductCore
{
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function getProduct()
    {
        return parent::query('getItemDetailInfo', 'GET', $this->getRequestParams());
    }

    protected function getRequestParams()
    {
        if ($this->getSku()) {
            $requestParams['ItemCode'] = $this->getSku();
        }

        $requestParams['SellerCode'] = '';

        return $requestParams;
    }

}