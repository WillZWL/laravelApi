<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\PlatformMarketOrderService;
use App\Transformers\PlatformOrderTransformer;
use Dingo\Api\Routing\Helpers;

use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    use Helpers;

    private $orderService;

    public function __construct(PlatformMarketOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function getOrders(Request $request)
    {
        $orders = $this->orderService->getOrdersByStore($request);
        return $this->collection($orders, new PlatformOrderTransformer());
    }
}
