<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AllocationPlanService;
use App\Services\IwmsApi\IwmsFactoryWmsService;

use App\Http\Requests;

class AllocationPlanController extends Controller
{
    private $iwmsFactoryWmsService = null;
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
            return redirect("http://admincentre.eservicesgroup.com/order/integrated_order_fulfillment");
        } else {
            return "Execution processing ends";
        }
    }

    public function wmsAllocationPlan(Requests\IwmsAllocationRequest $request)
    {
        $requestData = $request->all();
        if (isset($requestData['warehouse'])
            && $requestData['warehouse']
            && isset($requestData['soIds'])
            && $requestData['soIds']
        ) {
            return $this->getIwmsFactoryWmsService()->requestAllocationPlan($requestData['warehouse'], $requestData['soIds']);
        }
    }

    public function getIwmsFactoryWmsService()
    {
        if ($this->iwmsFactoryWmsService === null) {
            $this->iwmsFactoryWmsService = new IwmsFactoryWmsService();
        }
        return $this->iwmsFactoryWmsService;
    }
}
