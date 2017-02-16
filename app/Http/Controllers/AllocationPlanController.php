<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AllocationPlanService;

use App\Http\Requests;

class AllocationPlanController extends Controller
{
    private $allocationPlanService;

    public function __construct(AllocationPlanService $allocationPlanService)
    {
        $this->allocationPlanService = $allocationPlanService;
    }

    public function allocation($warehouseId, Request $request)
    {
        $requestData = $request->all();
        $this->allocationPlanService->getAllocationPlan($warehouseId, $requestData);
        if (isset($requestData['redirect_url']) && $requestData['redirect_url']) {
            return redirect($requestData['redirect_url']);
        } else {
            return "Execution processing ends";
        }
    }
}
