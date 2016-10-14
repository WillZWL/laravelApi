<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterProductUpdate extends PriceMinisterProductsCore
{

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
    }

    public function submitXmlFile($xmlFile)
    {   
        $xmlData =fopen($xmlFile, "r");
        return parent::curlPostXmlFileToApi($this->getRequestParams(), $xmlData);
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['action'] = 'genericimportfile';
        $requestParams['version'] = '2015-02-02';
        return  $requestParams;
    }

    public function setUrlBase()
    {
        $url = $this->urlbase.'stock_ws';
        $this->urlbase = $url;
    }
}
