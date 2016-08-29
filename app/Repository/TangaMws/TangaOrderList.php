<?php

namespace App\Repository\TangaMws;

class TangaOrderList extends TangaOrderCore
{
    private $startAt;
    private $endAt;

    public function __construct($store)
    {
        parent::__construct($store);
        $this->settangaPath();
    }

    public function fetchOrderList()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();

        if ($this->getStartAt()) {
            $requestParams['start_at'] = $this->getStartAt();
        }
        if ($this->getEndAt()) {
            $requestParams['end_at'] = $this->getEndAt();
        }

        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data)) {
            return parent::fix($data);
        }

        return null;
    }

    public function setTangaPath()
    {
        $this->tangaPath = 'api/vendors/'.$this->options['vendorAppId'].'/inventory_report';
    }

    public function getStartAt()
    {
        return $this->startAt;
    }

    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;
    }

    public function getEndAt()
    {
        return $this->endAt;
    }

    public function setEndAt($endAt)
    {
        $this->endAt = $endAt;
    }
}
