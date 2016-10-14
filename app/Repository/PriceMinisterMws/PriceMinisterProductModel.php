<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterProductModel extends PriceMinisterProductsCore
{
    private $productType;
    private $scope;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
    }

    public function fetchProductModel()
    {   
        return parent::query($this->getRequestParams());
    }

    protected function prepare($data = array())
    {
        if (isset($data['response'])) {
            return parent::fix($data['response']);
        }
        return null;
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['action'] = 'producttypetemplate';
        $requestParams['version'] = '2015-02-02';
        if($this->getProductType())
        $requestParams['alias'] = $this->getProductType();
        if($this->getScope())
        $requestParams['scope'] = $this->getScope();
        return $requestParams;
    }

    public function setUrlBase()
    {
        $url = $this->urlbase.'stock_ws';
        $this->urlbase = $url;
    }

    public function setProductType($value)
    {
        $this->productType = $value;
    }

    public function getProductType()
    {
        return $this->productType;
    }

    public function setScope($value)
    {
        $this->scope = $value;
    }

    public function getScope()
    {
        return $this->scope;
    }
}
