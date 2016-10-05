<?php

namespace App\Repository\LazadaMws;

class LazadaFeedStatus extends LazadaOrderCore
{
    private $feedId;

    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function fetchFeedStatus()
    {
        return parent::query($this->getRequestParams());
    }

    protected function getRequestParams()
    {
        $requestParams = parent::initRequestParams();
        $requestParams['Action'] = 'FeedStatus';
        if ($this->getFeedId()) {
            $requestParams['FeedID'] = $this->getFeedId();
        }
        return $requestParams;
    }

    protected function prepare($data = array())
    {
        if (isset($data['Body']) && isset($data['Body']['FeedDetail'])) {
            return parent::fix($data['Body']['FeedDetail']);
        }
        return null;
    }

    public function setFeedId($value)
    {
        $this->feedId = $value;
    }

    public function getFeedId()
    {
        return $this->feedId;
    }
}
