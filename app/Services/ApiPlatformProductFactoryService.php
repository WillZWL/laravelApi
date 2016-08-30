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

    public function getProductList($storeName)
    {
        return $this->apiPlatformProductInterface->getProductList($storeName);
    }

    public function submitProductPrice($storeName)
    {
        return $this->apiPlatformProductInterface->submitProductPrice($storeName);
    }

    public function submitProductInventory()
    {
        return $this->apiPlatformProductInterface->submitProductInventory();
    }
}
