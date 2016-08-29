<?php

namespace App\Repository\LazadaMws;

class LazadaMultipleOrderItems extends LazadaOrderCore
{
    private $orderIdList;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchMultipleOrderItems()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams["Action"] = "getGetMultipleOrderItems";
        if ($this->getOrderIdList()) {
            $requestParams["OrderIdList"] = $this->getOrderIdList();
        }
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
