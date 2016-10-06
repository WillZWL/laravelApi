<?php

namespace App\Repository\PriceMinisterMws;

class PriceMinisterImportReport extends PriceMinisterProductsCore
{
    private $version = '2011-11-29';
    private $fileId;
    private $nextToken;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setUrlBase();
    }

    public function getImportReport()
    {   
        return parent::query($this->getRequestParams());
    }

    public function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['action'] = 'genericimportreport';
        $requestParams['fileid'] = $this->getFileId();
        $requestParams['version'] = $this->version;
        if($this->getNextToken())
        $requestParams['nexttoken'] = $this->getNextToken();
        return  $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['response']) && isset($data['response']['product'])) {
            return parent::fix($data['response']['product']);
        }
        return null;
    }

    public function setUrlBase()
    {
        $url = $this->urlbase.'stock_ws';
        $this->urlbase = $url;
    }

    public function setFileId($value)
    {
        $this->fileId = $value;
    }

    public function getFileId()
    {
        return $this->fileId;
    }

    public function setNextToken($value)
    {
        $this->nextToken = $value;
    }

    public function getNextToken()
    {
        return $this->nextToken;
    }
}
