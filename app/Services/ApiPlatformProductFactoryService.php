<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;

class ApiPlatformProductFactoryService
{
    private $_requestData;

    public function __construct(ApiPlatformProductInterface $apiPlatformProductInterface)
    {
        $this->apiPlatformProductInterface = $apiPlatformProductInterface;
    }

    public function submitProductPriceAndInventory($storeName)
    {
        return $this->apiPlatformProductInterface->submitProductPriceAndInventory($storeName);
    }

    public function submitProductUpdate($storeName)
    {
        return $this->apiPlatformProductInterface->submitProductUpdate($storeName);
    }

    public function fulfilledInventoryReport($storeName)
    {
        return $this->apiPlatformProductInterface->fulfilledInventoryReport($storeName);
    }
}
