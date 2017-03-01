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
