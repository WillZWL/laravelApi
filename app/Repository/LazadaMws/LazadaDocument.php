<?php

namespace App\Repository\LazadaMws;

class LazadaDocument extends LazadaOrderCore
{
    private $documentType;
    private $orderItemIds;

    public function __construct($store) 
    {
        parent::__construct($store);
    }

    public function fetchDocument()
    {
      return parent::query($this->getRequestParams());
    }

    protected  function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams["Action"] = "GetDocument";
        if($this->getDocumentType())
        $requestParams["DocumentType"] = $this->getDocumentType();
        if($this->getOrderItemIds())
        $requestParams["OrderItemIds"] = $this->getOrderItemIds();
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data["Body"]) && isset($data["Body"]["Document"])) {
            return parent::fix($data["Body"]["Document"]);
        }
        return null;
    }

    public function setDocumentType($value)
    {
        $this->documentType = $value;
    }

    public function getDocumentType()
    {
        return $this->documentType;
    }

    public function setOrderItemIds($value)
    {
        $this->orderItemIds = $value;
    }

    public function getOrderItemIds()
    {
        return $this->orderItemIds;
    }
}