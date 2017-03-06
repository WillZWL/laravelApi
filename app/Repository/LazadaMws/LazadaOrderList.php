<?php

namespace App\Repository\LazadaMws;

class LazadaOrderList extends LazadaOrderCore
{
    private $createdAfter;
    private $createdBefore;
    private $updatedAfter;
    private $updatedBefore;
    private $status;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchOrderList()
    {
        try {
            return parent::query($this->getRequestParams());
        } catch (\Exception $e) {
            mail('will.zhang@eservicesgroup.com', 'Fetch Lazada Order Error', 'Error Message: '. $e->getMessage());
        }
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'GetOrders';
        if ($this->getCreatedAfter()) {
            $requestParams['CreatedAfter'] = $this->getCreatedAfter();
        }
        if ($this->getCreatedBefore()) {
            $requestParams['CreatedBefore'] = $this->getCreatedBefore();
        }
        if ($this->getUpdatedAfter()) {
            $requestParams['UpdatedAfter'] = $this->getUpdatedAfter();
        }
        if ($this->getUpdatedBefore()) {
            $requestParams['UpdatedBefore'] = $this->getUpdatedBefore();
        }
        if ($this->getStatus()) {
            $requestParams['Status'] = $this->getStatus();
        }

        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['Orders']) && isset($data['Body']['Orders']['Order'])) {
            return parent::fix($data['Body']['Orders']['Order']);
        }

        return null;
    }

    public function getCreatedAfter()
    {
        return $this->createdAfter;
    }

    public function setCreatedAfter($value)
    {
        $this->createdAfter = $value;
    }

    public function getCreatedBefore()
    {
        return $this->createdBefore;
    }

    public function setCreatedBefore($value)
    {
        $this->createdBefore = $value;
    }

    public function getUpdatedAfter()
    {
        return $this->updatedAfter;
    }

    public function setUpdatedAfter($value)
    {
        $this->updatedAfter = $value;
    }

    public function getUpdatedBefore()
    {
        return $this->updatedBefore;
    }

    public function setUpdatedBefore($value)
    {
        $this->updatedBefore = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }
}
