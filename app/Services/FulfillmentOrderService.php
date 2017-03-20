<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Repository\FulfillmentOrderRepository;
use App\Services\CourierInfoService;
use Excel;

class FulfillmentOrderService
{
    use ApiPlatformTraitService;

    public function __construct(FulfillmentOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getOrders(Request $request)
    {
        $orders = $this->orderRepository->getOrders($request);

        return $orders;
    }

    public function dashboard()
    {
        $merchantPendingOrdersCount = $this->getMerchantPendingOrdersCount();
        $merchantAllPaidOrdersCount = $this->getMerchantAllPaidOrdersCount();
        $merchantAllocatedOrdersCount = $this->getMerchantAllocatedOrdersCount();

        $data['all_paid_orders_count'] = $merchantAllPaidOrdersCount['total_count'];
        $data['merchant_all_paid_orders_count'] = $merchantAllPaidOrdersCount['list'];

        $data['pending_paid_orders_count'] =  $merchantPendingOrdersCount['total_count'];
        $data['merchant_pending_orders_count'] = $merchantPendingOrdersCount['list'];

        $data['allocated_orders_count'] = $merchantAllocatedOrdersCount['total_count'];
        $data['merchant_allocated_orders_count'] = $merchantAllocatedOrdersCount['list'];

        return $data;
    }

    public function picklistCount(Request $request)
    {
        $request->merge([
            'status' => 5,
            'refund_status' => 0,
            'hold_status' => 0,
            'prepay_hold_status' => 0,
            'merchant_hold_status' => 0,
            'exist_pick_list_no' => 1
        ]);
        return $this->orderRepository->getPickListCount($request);
    }

    public function getMerchantAllocatedOrdersCount()
    {
        $request = new Request;
        $request->merge([
            'status' => 5,
            'refund_status' => 0,
            'hold_status' => 0,
            'prepay_hold_status' => 0,
            'merchant_hold_status' => 0
        ]);
        return $this->orderRepository->getMerchantOrdersCount($request);
    }

    public function getMerchantAllPaidOrdersCount()
    {
        $request = new Request;
        $request->merge([
            'status' => 3,
            'exclude_lack_balance' => 0
        ]);
        return $this->orderRepository->getMerchantOrdersCount($request);
    }

    public function getMerchantPendingOrdersCount()
    {
        $request = new Request;
        $request->merge([
            'status' => 3,
            'refund_status' => 0,
            'hold_status' => 0,
            'prepay_hold_status' => 0,
            'merchant_hold_status' => 0,
            'exclude_lack_balance' => 1
        ]);
        return $this->orderRepository->getMerchantOrdersCount($request);
    }

    public function exportExcel(Request $request)
    {
        $request->merge(['per_page' => 5000]);
        $orders = $this->orderRepository->getOrders($request);
        $cellData[] = [
            'So No',
            'Platform Id',
            'Merchant',
            'Create Date',
            'Courier',
            'Order Type',
            'Country',
            'Currency',
            'Amount',
            'Items'
        ];
        if (!$orders->isEmpty()) {
            foreach ($orders as $order) {
                $courierName = $this->getCourierNameById($order->esg_quotation_courier_id);
                $amount = number_format($order->amount, 2, '.', '');
                $create_date = date('Y-m-d', strtotime($order->order_create_date));
                $items = '';
                foreach ($order->soItemDetail as $item) {
                    $items .= '('. $item->item_sku.'--'. $item->qty.')';
                }
                $cellData[] = [
                    $order->so_no,
                    $order->platform_id,
                    $order->sellingPlatform->merchant_id,
                    $create_date,
                    $courierName,
                    $order->sellingPlatform->type,
                    $order->delivery_country_id,
                    $order->currency_id,
                    $amount,
                    $items
                ];
            }
            if ($orders->count() == 5000) {
                $cellData[] = ["In order to download speed, This file limit of 5000 records. If You need all, ask IT for help"];
            }
        } else {
            $cellData[] = ['No Any Records'];
        }
        $path = storage_path('fulfillment-order-feed/');
        $cellDataArr['Orders'] = $cellData;
        $fileName = 'Orders List';
        $excelFile = $this->generateMultipleSheetsExcel($fileName, $cellDataArr, $path);
        return $excelFile["path"].$excelFile["file_name"];
    }

    public function getCourierNameById($id)
    {
        $courierService = new CourierInfoService();

        return $courierService->getCourierNameById($id);
    }
}
