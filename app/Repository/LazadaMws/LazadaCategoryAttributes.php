<?php

namespace App\Repository\LazadaMws;

class LazadaCategoryAttributes extends LazadaProductsCore
{
    private $_requestParams = array();
    private $primaryCategory;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchCategoryAttributes()
    {
        return parent::query($this->getRequestParams());
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetCategoryAttributes';
        $requestParams['PrimaryCategory'] = $this->getPrimaryCategory();
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        return isset($data['Body']) ? $data['Body'] : null;
    }

    public function getPrimaryCategory()
    {
        return $this->primaryCategory;
    }

    public function setPrimaryCategory($value)
    {
        $this->primaryCategory = $value;
    }
}
