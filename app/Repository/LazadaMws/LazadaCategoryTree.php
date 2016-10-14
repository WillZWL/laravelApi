<?php

namespace App\Repository\LazadaMws;

class LazadaCategoryTree extends LazadaProductsCore
{
    
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchCategoryTree()
    {
        return parent::query($this->getRequestParams());
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetCategoryTree';
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['Category'])) {
            return parent::fix($data['Body']['Category']);
        }
        return null;
    }

}
