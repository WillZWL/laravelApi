<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiPlatformFactoryService;

class InterfacePlatformOrder extends Controller
{
    public function __construct(ApiPlatformFactoryService $apiPlatformFactoryService)
    {
        $this->apiPlatformFactoryService = $apiPlatformFactoryService;
    }

    public function retrieveOrder(Request $request)
    {
        //$storeName="VBLAZADAMY";
        $storeName = 'VBLAZADAMY';
        $schedule = $this->apiPlatformFactoryService->getStoreSchedule($storeName);
        $orderList = $this->apiPlatformFactoryService->retrieveOrder($storeName, $schedule);
    }

    public function getOrderList(Request $request)
    {
        $orderList = $this->apiPlatformFactoryService->getOrderList($storeName);
    }

    public function getOrderItemList()
    {
        $orderItemList = $this->apiPlatformFactoryService->getOrderItemList($storeName);
    }

    public function getOrder(Request $request)
    {
        $order = $this->apiPlatformFactoryService->getOrder($storeName);
    }

    public function getProductList(Request $request)
    {
        $order = $this->apiPlatformFactoryService->getProductList($storeName);
    }

    public function setStatusToCanceled(Request $request)
    {
        $order = $this->apiPlatformFactoryService->setStatusToCanceled($storeName);
    }

    public function setStatusToPackedByMarketplace(Request $request)
    {
        $order = $this->apiPlatformFactoryService->setStatusToPackedByMarketplace($storeName);
    }
}
