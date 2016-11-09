<?php

namespace App\Repository\LazadaMws;

class LazadaProductList extends LazadaProductsCore
{
    private $_requestParams = array();

    public function __construct($store)
    {
        parent::__construct($store);
        $this->getRequestParams();
    }

    public function fetchProductList()
    {
        return parent::query($this->_requestParams);
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $this->_requestParams = array_merge($this->_requestParams, $requestParams);
        $this->_requestParams['Action'] = 'GetProducts';
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['Products']) && isset($data['Body']['Products']['Product'])) {
            return parent::fix($data['Body']['Products']['Product']);
        }

        return null;
    }

    public function setCreatedAfter($value)
    {
        $this->_requestParams['CreatedAfter'] = $value;
    }

    public function setCreatedBefore($value)
    {
        $this->_requestParams['CreatedBefore'] = $value;
    }

    public function setSearch($value)
    {
        $this->_requestParams['Search'] = $value;
    }

    public function setFilter($value)
    {
        $this->_requestParams['Filter'] = $value;
    }

    public function setLimit($value)
    {
        $this->_requestParams['Limit'] = $value;
    }

    public function setOffset($value)
    {
        $this->_requestParams['Offset'] = $value;
    }

    public function setSkuSellerList($value)
    {
        $this->_requestParams['SkuSellerList'] = json_encode($value);
    }

    public function setUpdatedAfter($value)
    {
        $this->_requestParams['UpdatedAfter'] = $value;
    }

    public function setUpdatedBefore($value)
    {
        $this->_requestParams['UpdatedBefore'] = $value;
    }

    public function setGlobalIdentifier($value)
    {
        $this->_requestParams['GlobalIdentifier'] = $value;
    }
}
