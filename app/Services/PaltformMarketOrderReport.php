<?php

namespace App\Services;

use App\Contracts\ApiPlatformInterface;

class PaltformMarketOrderReport
{
    private $_requestData;

    public function __construct(ApiPlatformInterface $apiPlatformInterface)
    {
        $this->apiPlatformInterface = $apiPlatformInterface;
    }

    public function getOrderAlert()
    {
    }

    public function getMissOrder()
    {
    }
}
