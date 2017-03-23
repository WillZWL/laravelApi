<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\OrderSettlementService;
use App\Transformers\OrderSettlementTransformer;
use App\Services\SettlementPreviewService;
use Dingo\Api\Routing\Helpers;

class OrderSettlementController extends Controller
{
    use Helpers;

    use \App\Services\TraitDeclaredService;

    use \App\Services\ApiPlatformTraitService;

    private $orderSettlementService;

    public function __construct(OrderSettlementService $orderSettlementService)
    {
        $this->orderSettlementService = $orderSettlementService;
    }

    public function index(Request $request)
    {
        $orders = $this->orderSettlementService->getOrders($request);

        if ($request->get('download')) {
            $excelFile = $this->exportExcel($request);
            if ($excelFile) {
                return response()->download($excelFile);
            }
        }

        return $this->response->paginator($orders, new OrderSettlementTransformer());
    }

    public function bulkUpdate(Request $request)
    {
        return $this->orderSettlementService->bulkUpdate($request);
    }

    public function sendEmail(Request $request)
    {
        return $this->orderSettlementService->sendEmail($request);
    }

    public function exportExcel(Request $request)
    {
        $request->merge(['per_page' => 10000]);
        $orders = $this->orderSettlementService->getOrders($request);
        $cellData[] = [
            'Payment Gateway',
            'Business Type',
            'Transaction ID',
            'SO Number',
            'Platform Order No',
            'Order Create Date',
            'Dispatch Date',
            'Currency',
            'Amount',
            'Estimated Settlement Amount',
            'Validation Status',
            'Estimated Settlement Date',
            'Marketplace Main Contact Person',
            'Marketplace Email address(es)',
            'Marketplace Contact Number'
        ];
        if (!$orders->isEmpty()) {
            foreach ($orders as $order) {
                $estimated_settlement_amount = $this->getEstimatedSettlementAmount($order);
                $estimated_settlement_date = $this->getEstimatedSettlementDate($order);
                $validation_status = $this->getValidationStatus($order->validation_status);
                $cellData[] = [
                    $order->payment_gateway_id,
                    $order->biz_type,
                    $order->txn_id,
                    $order->so_no,
                    $order->platform_order_id,
                    $order->order_create_date,
                    $order->dispatch_date,
                    $order->currency_id,
                    $order->amount,
                    $estimated_settlement_amount,
                    $validation_status,
                    $estimated_settlement_date,
                    $order->marketplace_contact_name,
                    trim($order->marketplace_email_1)." \r\n ".trim($order->marketplace_email_2)." \r\n ".trim($order->marketplace_email_3),
                    $order->marketplace_contact_phone
                ];
            }
            if ($orders->count() == 10000) {
                $cellData[] = ['There may more records'];
            }
        } else {
            $cellData[] = ['No Any Records'];
        }
        $path = storage_path('/app/');
        $cellDataArr['Orders'] = $cellData;
        $fileName = 'Marketplace_Settlement_Exception_List';
        $excelFile = $this->generateMultipleSheetsExcel($fileName, $cellDataArr, $path);
        return $excelFile["path"].$excelFile["file_name"];
    }

    public function getEstimatedSettlementAmount($order)
    {
        $settlementPreviewService = new SettlementPreviewService();
        return $settlementPreviewService->calculateEstimatedAmount($order);
    }

    public function getValidationStatus($validationStatus)
    {
        $validationStatusArr = [
            0 => 'Unverified',
            1 => 'Verified > Email sent to MP 1',
            2 => 'Verified > Email sent to MP 2',
            3 => 'Verified > Email sent to MP 3',
            4 => 'Verified > MP release settlement within 14 days',
            5 => 'Verified > MP reject',
            6 => 'Verified > Order is cancelled in MP',
            7 => 'Verified > Order is returning to us',
            8 => 'Verified > Order is returned to us',
            9 => 'Verified > Called to MP',
            10 => 'Verified > FIN to check the bank account',
            11 => 'Verified > Others',
            12 => 'Closed > Settlement received'
        ];
        return $validationStatusArr[$validationStatus];
    }
}
