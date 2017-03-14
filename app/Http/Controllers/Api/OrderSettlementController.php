<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\OrderSettlementService;
use App\Transformers\OrderSettlementTransformer;
use Dingo\Api\Routing\Helpers;

class OrderSettlementController extends Controller
{
    use Helpers;

    private $orderSettlementService;

    public function __construct(OrderSettlementService $orderSettlementService)
    {
        $this->orderSettlementService = $orderSettlementService;
    }

    public function index(Request $request)
    {
        $orders = $this->orderSettlementService->getOrders($request);

        return $this->response->paginator($orders, new OrderSettlementTransformer());
    }
}
