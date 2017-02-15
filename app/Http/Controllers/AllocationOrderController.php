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
        dd($request->all());
        return $this->allocationPlanService->getAllocationPlan($warehouseId, $request->all());
    }
}
