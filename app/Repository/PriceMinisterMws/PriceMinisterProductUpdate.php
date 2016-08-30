<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterProductUpdate extends PriceMinisterProductsCore
{
    private $_requestParams = array();

    public function __construct($store)
    {
        parent::__construct($store);
        $this->getRequestParams();
    }

    public function submitXmlData($xmlData)
    {   
        //post file to PM
        $xmlData =array(
            "file" => $xmlData
        );
        return parent::curlPostDataToApi($this->_requestParams, $xmlData);
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $this->_requestParams = array_merge($this->_requestParams, $requestParams);
        $this->_requestParams['action'] = 'genericimportfile';
    }
}
