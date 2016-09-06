<?php

namespace App\Repository\NeweggMws;

class NeweggOrderList extends NeweggOrderCore
{
    private $orderDateFrom;
    private $orderDateTo;
    private $status;
    private $resourceMethod;
    private $xsdFile = "\OrderMgmt\GetOrderInfo\GetOrderInfoRequest.xsd";
    private $resourceUrl = "ordermgmt/order/orderinfo";

    public function __construct($store)
    {
        parent::__construct($store);
        $this->setResourceMethod("PUT");
        //test demo 2
    }

    public function fetchOrderList()
    {
        $requestBody = $this->getRequestBody();
        $requestParams = $this->getRequestParams();
        return parent::query($this->getResourceUrl(), $this->getResourceMethod(), $requestParams, $requestBody); 
    }

    protected function getRequestParams()
    {
        $requestParams = ["version"=>"304"];
        return $requestParams;
    }

    protected function getRequestBody()
    {
        if ($this->getOrderDateTo()) {
            $requestParams['OrderDateTo'] = $this->getOrderDateTo();
        }
        if ($this->getStatus()) {
            $requestParams['Status'] = $this->getStatus();
        }

        $requestXml[] = "<NeweggAPIRequest>";
        $requestXml[] = "<OperationType>GetOrderInfoRequest</OperationType>";
        $requestXml[] = "<RequestBody>";
        $requestXml[] = "<PageIndex>1</PageIndex>";
        $requestXml[] = "<RequestCriteria>";

        if ($this->getStatus()) {
            $requestXml[] = "<Status>" . $this->getStatus() . "</Status>";
        }
        if ($this->getOrderDateFrom()) {
            $requestXml[] = "<OrderDateFrom>" . $this->getOrderDateFrom() . "</OrderDateFrom>";
        }
        if ($this->getOrderDateTo()) {
            $requestXml[] = "<OrderDateTo>" . $this->getOrderDateTo() . "</OrderDateTo>";
        }

        $requestXml[] = "</RequestCriteria>";
        $requestXml[] = "</RequestBody>";
        $requestXml[] = "</NeweggAPIRequest>";
    
        // libxml_use_internal_errors(true);
        // $xml = new \DOMDocument();
        // $xml->loadXML($requestXml); 
        // if(!$xml->schemaValidate(app_path("Repository/NeweggMws/xsd/OrderMgmt/GetOrderInfo/GetOrderInfoRequest.xsd"))) {
        //     $errors = libxml_get_errors();
        // }
        return implode("\n", $requestXml);
    }

    // protected function prepare($data = array())
    // {
    //     if (isset($data['Body']) && isset($data['Body']['Orders']) && isset($data['Body']['Orders']['Order'])) {
    //         return parent::fix($data['Body']['Orders']['Order']);
    //     }

    //     return null;
    // }

    public function getOrderDateFrom()
    {
        return $this->orderDateFrom;
    }

    public function setOrderDateFrom($value)
    {
        $this->orderDateFrom = $value;
    }

    public function getOrderDateTo()
    {
        return $this->orderDateTo;
    }

    public function setOrderDateTo($value)
    {
        $this->orderDateTo = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getResourceMethod()
    {
        return $this->resourceMethod;
    }

    public function setResourceMethod($value)
    {
        $this->resourceMethod = $value;
    }

    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    public function setResourceUrl($value)
    {
        $this->resourceUrl = $value;
    }
}
