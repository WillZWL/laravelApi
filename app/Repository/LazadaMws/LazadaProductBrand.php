<?php

namespace App\Repository\LazadaMws;

class LazadaProductBrand extends LazadaProductsCore
{
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchBrandList()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetBrands';
        $requestParams['Limit'] = $this->getLimit();
        $requestParams['Offset'] = $this->getOffset();
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['Brands']) && isset($data['Body']['Brands']['Brand'])) {
            return parent::fix($data['Body']['Brands']['Brand']);
        }
        return null;
    }

}
