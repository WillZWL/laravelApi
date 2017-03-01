<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AllocationPlanService;
use App\Services\IwmsApi\IwmsCoreService;
use App\Services\IwmsApi\IwmsFactoryWmsService;

use App\Http\Requests;

class AllocationPlanController extends Controller
{
    private $iwmsFactoryWmsService = null;
    private $iwmsCoreService = null;
    private $allocationPlanService;

    public function __construct(AllocationPlanService $allocationPlanService)
    {
        $this->allocationPlanService = $allocationPlanService;
    }

    public function allocation($warehouseId, Request $request)
    {
        return "Sorry, Guys, Manual allocation plan is no longer available here, It will by program progress.";
        // $requestData = $request->all();
        // $this->allocationPlanService->getAllocationPlan($warehouseId, $requestData);
        // if (isset($requestData['redirect_url']) && $requestData['redirect_url']) {
        //     return redirect("http://admincentre.eservicesgroup.com/order/integrated_order_fulfillment");
        // } else {
        //     return "Execution processing ends";
        // }
    }

    public function wmsAllocationPlan(Requests\IwmsAllocationRequest $request)
    {
        $requestData = $request->all();
        if (isset($requestData['warehouse']) && $requestData['warehouse']) {
            $wmsPlatform = $this->getIwmsCoreService()->getWmsPlatformByWarehouse($requestData['warehouse']);
            if ($wmsPlatform) {
                return $this->getIwmsFactoryWmsService($wmsPlatform)->requestAllocationPlan($requestData);
            }
        }
    }

    public function getIwmsFactoryWmsService($wmsPlatform)
    {
        if ($this->iwmsFactoryWmsService === null) {
            $this->iwmsFactoryWmsService = new IwmsFactoryWmsService($wmsPlatform);
        }
        return $this->iwmsFactoryWmsService;
    }

    public function getIwmsCoreService()
    {
        if ($this->iwmsCoreService === null) {
            $this->iwmsCoreService = new IwmsCoreService();
        }
        return $this->iwmsCoreService;
    }
}
