<<?php

namespace App\Repository\LazadaMws;

class LazadaDocument extends LazadaOrderCore
{
    private $orderIdList;

    public function __construct($store) 
    {
        parent::__construct($store);
    }

    protected  function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams["Action"] = "getGetMultipleOrderItems";
        if($this->getOrderIdList())
        $requestParams["OrderIdList"] = $this->getOrderIdList();
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data["Body"]) && isset($data["Body"]["Orders"]) && isset($data["Body"]["Orders"]["Order"])) {
            return parent::fix($data["Body"]["Orders"]["Order"]);
        }
        return null;
    }

    public function setOrderIdList($value)
    {
        $this->orderIdList = $value;
    }

    public function getOrderIdList()
    {
        return $this->orderIdList;
    }

}