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
    public function getCommissionChargeReport(Requests\CommissionCharge\CommissionChargeReportRequest $request)
    {
        $data = $this->commssionChargeService->commissionChargeData($request->all());

        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=commission-charge-report.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function() use ($data) {
            $FH = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, $headers);
    }
}
