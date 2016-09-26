<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Repository\PlatformMarketOrderRepository;

class PlatformMarketOrderService
{
    private $orderRepository;

    public function __construct(PlatformMarketOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getOrdersByStore(Request $request)
    {
        return $this->orderRepository->getOrdersByStore($request);
    }
}
