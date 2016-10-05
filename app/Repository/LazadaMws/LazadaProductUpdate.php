<?php

namespace App\Repository\LazadaMws;

class LazadaProductUpdate extends LazadaProductsCore
{
    private $_requestParams = array();

    public function __construct($store)
    {
        parent::__construct($store);
        $this->getRequestParams();
    }

    public function submitXmlData($xmlData)
    {
        $responXml = parent::curlPostDataToApi($this->_requestParams, $xmlData);
        $responData = new \SimpleXMLElement($responXml);
        return  (string) $responData->Head->RequestId;
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $this->_requestParams = array_merge($this->_requestParams, $requestParams);
        $this->_requestParams['Action'] = 'ProductUpdate';
    }

    protected function prepare($data = array())
    {
        if (isset($data['Head']) && isset($data['Head']['RequestId']) && isset($data['Head']['RequestAction'])) {
            return $data['Head'];
        }
        return null;
    }
}
