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
        $endAt = date(\DateTime::ISO8601, strtotime(date("Y-m-d H:i:s")));
        $this->setEndAt($endAt);
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
        $this->tangaPath = 'api/vendors/'. $this->vendorAppId .'/unshipped_items';
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
