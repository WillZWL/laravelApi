<?php

namespace App\Repository\LazadaMws;

class LazadaQcStatus extends LazadaProductsCore
{
    private $skuSellerList;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchQcStatus()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetQcStatus';
        if ($this->getDocumentType()) {
            $requestParams['SkuSellerList'] = $this->getSkuSellerList();
        }
        $requestParams['Offset'] = $this->getOffset();
        $requestParams['Limit'] = $this->getLimit();
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['Status'])) {
            return parent::fix($data['Body']['Status']);
        }
        return null;
    }

    public function setSkuSellerList($value)
    {
        $this->skuSellerList = $value;
    }

    public function getSkuSellerList()
    {
        return $this->skuSellerList;
    }
}
