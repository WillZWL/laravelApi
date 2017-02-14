<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Services\FulfillmentOrderService;
use App\Http\Controllers\Controller;
use App\Transformers\FulfillmentOrderTransformer;
use Dingo\Api\Routing\Helpers;

class FulfillmentOrderController extends Controller
{
    use Helpers;

    private $orderService;

    public function __construct(FulfillmentOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $orders = $this->orderService->getOrders($request);

        return $this->response->paginator($orders, new FulfillmentOrderTransformer());
    }
}
