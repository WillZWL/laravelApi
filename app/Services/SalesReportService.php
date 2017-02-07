<?php

namespace App\Services;

use App\Http\Requests\SalesReportRequest;
use App\Repository\SalesReportRepository;

class SalesReportService
{
    private $salesReportRepository;

    public function __construct(SalesReportRepository $salesReportRepository)
    {
        $this->salesReportRepository = $salesReportRepository;
    }

    public function getSalesReportContent(SalesReportRequest $request)
    {
        $orders = $this->salesReportRepository->getSalesOrderInfo($request);
        $orders->load('soItemDetail');
        $orders->load('salesOrderStatistic');

        $formattedOrder = $orders->map(function ($order) {
            return $order->salesOrderStatistic->map(function ($item) use ($order) {
                return [
                    'SO number' => $order->so_no,
                    'ESG SKU' => $item->esg_sku,
                    'Item number' => $item->line_no,
                    'Quantity' => $item->qty,
                    'Platform' => $order->platform_id,
                    'Order created date' => $order->order_create_date,
                    'Currency' => $order->currency_id,
                    'Selling price' => $item->selling_price,
                    'Shipping cost' => $item->shipping_cost,
                    'Supplier cost' => $item->supplier_cost,
                    'Marketplace fee' => $item->marketplace_fee,
                    'FBA/SBN fee' => $item->fulfilment_by_marketplace_fee,
                    'Profit' => $item->profit,
                    'Profit in USD' => round($item->profit * $item->to_usd_rate, 2),
                    'Margin' => $item->margin / 100,
                    'business_unit' => $item->product->brand->business_unit,
                    'buyer' => $item->buyer,
                    'operator' => $item->operator,
                ];
            });
        })->collapse();

        if ($businessUnit = $request->get('business_unit')) {
            $formattedOrder = $formattedOrder->reject(function ($item) use ($businessUnit) {
                return $item['business_unit'] != $businessUnit;
            });
        }

        if ($buyer = $request->get('selectedBuyer')) {
            $formattedOrder = $formattedOrder->reject(function ($item) use ($buyer) {
                return $item['buyer'] != $buyer;
            });
        }

        if ($operator = $request->get('selectedOperator')) {
            $formattedOrder = $formattedOrder->reject(function ($item) use ($operator) {
                return $item['buyer'] != $operator;
            });
        }

        $excel = \Excel::create('accelerator_sales_report', function ($excel) use ($formattedOrder) {
            $excel->sheet('first', function ($sheet) use ($formattedOrder) {
                $sheet->fromArray(array_filter($formattedOrder->toArray()));
            });
        });

        return $excel;
    }
}