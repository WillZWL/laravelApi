<?php

namespace App\Http\Controllers\Api;

use App\Services\CommissionChargeService;
// use App\Transformers\CommissionChargeTransformer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Excel;

class CommissionChargeController extends Controller
{
    use Helpers;

    private $commissionChargeService;

    public function __construct(CommissionChargeService $commissionChargeService)
    {
        $this->commssionChargeService = $commissionChargeService;
    }

    /**
     * download Commission Charge Report of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAmazonCommissionChargeReport($flexBatchId)
    {
        $excel = $this->commssionChargeService->amazonCommissionChargeExport($flexBatchId);

        return $excel->download('csv');
    }
}
