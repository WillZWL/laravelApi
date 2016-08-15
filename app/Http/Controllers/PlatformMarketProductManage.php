<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiPlatformProductFactoryService;

class  PlatformMarketProductManage extends Controller
{
    public function __construct(ApiPlatformProductFactoryService $apiPlatformProductFactoryService)
    {
        $this->apiPlatformProductFactoryService=$apiPlatformProductFactoryService;
    }

    public function getProductList(Request $request)
    {
        $storeName="BCLAZADAMY";
        //$schedule=$this->apiPlatformProductFactoryService->getStoreSchedule($storeName);
        $productList=$this->apiPlatformProductFactoryService->getProductList($storeName);
    }

    public function submitProductPrice(Request $request)
    {
        $order=$this->apiPlatformProductFactoryService->submitProductPrice();
    }

    public function submitProductInventory(Request $request)
    {
        $order=$this->apiPlatformProductFactoryService->submitProductInventory();
    }

}
