<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterProductList extends PriceMinisterProductsCore
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
        $this->_requestParams['action'] = 'listing';
    }

    protected function prepare($data = array())
    {
        if (isset($data['response']) && isset($data['response']['products'])) {
            return parent::fix($data['response']);
        }

        return null;
    }

    public function setScope($value)
    {
        $this->_requestParams['scope'] = $value;
    }

    public function setCreatedBefore($value)
    {
        $this->_requestParams['kw'] = $value;
    }

    public function setSearch($value)
    {
        $this->_requestParams['nav'] = $value;
    }

    public function setFilter($value)
    {
        $this->_requestParams['refs'] = $value;
    }

    public function setLimit($value)
    {
        $this->_requestParams['productids'] = $value;
    }

    public function setOffset($value)
    {
        $this->_requestParams['nbproductsperpage'] = $value;
    }

    public function setSkuSellerList($value)
    {
        $this->_requestParams['pagenumber'] = $value;
    }

}
