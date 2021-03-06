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

        if ($request->get('export')) {
            $excelFile = $this->orderService->exportExcel($request);
            if ($excelFile) {
                return response()->download($excelFile);
            }
        } else {
            return $this->response->paginator($orders, new FulfillmentOrderTransformer());
        }
    }

    public function dashboard(Request $request)
    {
        if ($request->get('download')) {
            $excelFile = $this->orderService->exportOrderCountToExcel();
            if ($excelFile) {
                return response()->download($excelFile);
            }
        } else {
            return $this->orderService->dashboard();
        }
    }

    public function picklistCount(Request $request)
    {
        if ($request->get('download')) {
            $excelFile = $this->orderService->exportPickListCountToExcel();
            if ($excelFile) {
                return response()->download($excelFile);
            }
        } else {
            return $this->orderService->picklistCount($request);
        }
    }
}
