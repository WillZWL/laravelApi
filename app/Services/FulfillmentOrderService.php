<?php

namespace App\Services;

use Illuminate\Http\Request;

use App\Repository\FulfillmentOrderRepository;

class FulfillmentOrderService
{

    public function __construct(FulfillmentOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getOrders(Request $request)
    {
        $orders = $this->orderRepository->getOrders($request);

        return $orders;
    }
}
