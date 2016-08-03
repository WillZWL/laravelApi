<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiPlatformFactoryService;

class  PlatformMarketProductManage extends Controller
{
    public function __construct(ApiPlatformFactoryService $apiPlatformFactoryService)
    {
        $this->apiPlatformFactoryService=$apiPlatformFactoryService;
    }

    public function getProductList(Request $request)
    {
        $storeName="BCLAZADAMY";
        //$schedule=$this->apiPlatformFactoryService->getStoreSchedule($storeName);
        $productList=$this->apiPlatformFactoryService->getProductList($storeName);
    }

    public function submitProductPriceOrInventory(Request $request)
    {
        $action="pendingInventory";
        //$schedule=$this->apiPlatformFactoryService->getStoreSchedule($storeName);
        $order=$this->apiPlatformFactoryService->submitProductPriceOrInventory($action);
    }

}
