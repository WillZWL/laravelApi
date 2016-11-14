<?php

namespace App\Repository\LazadaMws;

class LazadaUpdatePriceQuantity extends LazadaProductsCore
{
    private $_requestParams = array();

    public function __construct($store)
    {
        parent::__construct($store);
        $this->getRequestParams();
    }

    public function submitXmlData($xmlData)
    {
        return parent::curlPostDataToApi($this->_requestParams, $xmlData);
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $this->_requestParams = array_merge($this->_requestParams, $requestParams);
        $this->_requestParams['Action'] = 'UpdatePriceQuantity';
    }

    protected function prepare($data = array())
    {
        if (isset($data['Head']) && isset($data['Head']['RequestId']) && isset($data['Head']['RequestAction'])) {
            return $data['Head'];
        }
        return null;
    }
}
