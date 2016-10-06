<?php

namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\PlatformMarketProductFeed;

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

    public function warehouseInventoryReport()
    {
        return $this->apiPlatformProductInterface->warehouseInventoryReport();
    }

    public function getEsgUnSuppressedReport()
    {
        return $this->apiPlatformProductInterface->getEsgUnSuppressedReport();
    }

    public function getProductUpdateFeedBack($storeName)
    {
        $productUpdateFeeds = PlatformMarketProductFeed::ProductFeed($storeName,'_SUBMITTED_');
        if(!$productUpdateFeeds->isEmpty()){
            return $this->apiPlatformProductInterface->getProductUpdateFeedBack($storeName,$productUpdateFeeds);
        }
        
    }
}
