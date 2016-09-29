<?php

namespace App\Services;

use App\User;
use Illuminate\Http\Request;
use App\Repository\PlatformMarketOrderRepository;

class PlatformMarketOrderService
{
    private $orderRepository;

    public function __construct(PlatformMarketOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getOrders(Request $request)
    {
        $stores = User::find(\Authorizer::getResourceOwnerId())->stores()
            ->pluck('store_id')->all();

        return $this->orderRepository->getOrdersByStore($request, $stores);
    }

    public function getOrderDetails($id)
    {
        return $this->orderRepository->getOrderDetails($id);
    }
}
