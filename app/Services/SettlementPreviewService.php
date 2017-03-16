<?php

namespace App\Services;

use App\Models\So;
use App\Services\BaseMailService;

class SettlementPreviewService
{
    use BaseMailService;

    public function __construct()
    {
    }

    public function preview()
    {
        $startDate = $this->getLastMonday()." 00:00:00";
        $endDate = $this->getLastSunday()." 23:59:59";
        $shippedOrders = $this->getLastWeekOrder("shipped_date", $startDate, $endDate);
        $createdOrders = $this->getLastWeekOrder("order_create_date", $startDate, $endDate);
        $orders = $shippedOrders->merge($createdOrders);
        foreach ($orders as $row) {
            $row->estimated_amount = $this->calculateEstimatedAmount($row);
        }

        if ($cellDatas = $this->getSettlementCellDatas($orders)) {
            $this->sendSettlementPreviewEmail($this->getNotifyEmail(), $cellDatas);
        }
    }

    public function getNotifyEmail()
    {
        return "finance@eservicesgroup.net, gonzalo@eservicesgroup.com, celine@eservicesgroup.com";
    }

    public function getSettlementCellDatas($orders)
    {
        if (! $orders->isEmpty()) {
            $cellDatas = $orderCellData = $summaryCellData = [];
            $orderCellData[] = [
                                "Marketplace Account Status",
                                "Payment Gateway",
                                "Business Type",
                                "Transaction ID",
                                "SO Number",
                                "Platform Order Number",
                                "Order Create Date",
                                "Dispatch Date",
                                "Currency",
                                "Amount",
                                "Estimated Settlement Amount",
                                "Estimated Settlement Date"
                            ];
            $summary = [];
            foreach ($orders as $order) {
                $orderCellData[] = [
                    'payment_gateway_status' => $order->payment_gateway_status == 1 ? "Active" : "Inactive",
                    'payment_gateway' => $order->payment_gateway,
                    'biz_type' => $order->biz_type,
                    'txn_id' => $order->txn_id,
                    'so_no' => $order->so_no,
                    'platform_order_id' => $order->platform_order_id,
                    'order_create_date' => date("Y/m/d H:i", strtotime($order->order_create_date)),
                    'dispatch_date' => date("Y/m/d H:i", strtotime($order->dispatch_date)),
                    'currency_id' => $order->currency_id,
                    'amount' => $order->amount,
                    'estimated_amount' => $order->estimated_amount,
                    'estimated_date' => date("Y/m/d", strtotime($order->estimated_date))
                ];
                if (isset($summary[$order->payment_gateway.$order->currency_id])) {
                    $summary[$order->payment_gateway.$order->currency_id]->amount += $order->amount;
                    $summary[$order->payment_gateway.$order->currency_id]->estimated_amount += $order->estimated_amount;
                } else {
                    $summary[$order->payment_gateway.$order->currency_id] = $order;
                }
            }

            $summaryCellData[] = [
                                "Marketplace Account Status",
                                "Payment Gateway",
                                "Currency",
                                "Total Order Amount",
                                "Estimated Total Settlement Amount",
                                "Estimated Settlement Date",
                                "Week"
                            ];
            foreach ($summary as $row) {
                $summaryCellData[] = [
                                "payment_gateway_status"=> $row->payment_gateway_status == 1 ? "Active" : "Inactive",
                                "payment_gateway" => $row->payment_gateway,
                                "currency_id" => $row->currency_id,
                                'amount' => $row->amount,
                                'estimated_amount' => $row->estimated_amount,
                                'estimated_date' => date("M d, Y", strtotime($this->getLastMonday()))." - ".date("M d, Y", strtotime($this->getLastSunday())),
                                'week' => date('W')
                            ];
            }

            $cellDatas["Summary"] = $summaryCellData;
            $cellDatas["Order Detail"] = $orderCellData;

            return $cellDatas;
        }
    }

    private function sendSettlementPreviewEmail($toMail, $cellData)
    {
        $filePath = \Storage::disk('settlementPreview')->getDriver()->getAdapter()->getPathPrefix();
        $fileName = "settlement_preview_".date('Y')."_".date('W')."_week";

        if (!empty($cellData)) {
            $excelFile = $this->createExcelFile($fileName, $filePath, $cellData);
            if ($excelFile) {
                $subject = "Marketplace Settlement - Weekly Preview";
                $attachment = [
                    "path" => $filePath,
                    "file_name"=>$fileName .".xlsx"
                ];
                $template = "Hi FIN Team,".PHP_EOL
                            ."Attached is the weekly forecast for Marketplace settlements.".PHP_EOL
                            ."Please check if we receive the payment from each Marketplace and upload the Settlement Date in ESG admin (http://admincentre.eservicesgroup.com/account/gateway_report/settlement).".PHP_EOL
                            ."thank you.".PHP_EOL
                            ."ESG System".PHP_EOL.PHP_EOL;
                $this->setMailTemplate($template);
                $this->sendAttachmentMail(
                    $toMail,
                    $subject,
                    $attachment,
                    "",
                    "milo.chen@eservicesgroup.com"
                );
            }
        }
    }

    public function createExcelFile($fileName, $filePath, $cellDatas)
    {
        $excelFile = \Excel::create($fileName, function ($excel) use ($cellDatas) {
            foreach ($cellDatas as $sheetName => $cellData) {
                $excel->sheet($sheetName, function ($sheet) use ($cellData) {
                    $sheet->rows($cellData);
                });
            }
        })->store("xlsx", $filePath);
        if ($excelFile) {
            return true;
        }
    }

    public function getLastWeekOrder($dateType, $startDate, $endDate)
    {
        $dateField = "dispatch_date";
        if ($dateType == "order_create_date") {
            $dateField = "order_create_date";
        }
        $result = So::join("so_payment_status AS sps", "so.so_no", "=", "sps.so_no")
                ->join("payment_gateway AS pg", "pg.id", "=", "sps.payment_gateway_id")
                ->where("so.platform_group_order", "1")
                ->whereBetween(\DB::raw("DATE_ADD(so.$dateField,INTERVAL pg.settlement_date_day DAY)"), [$startDate, $endDate])
                ->where("pg.settlement_date_type", $dateType)
                ->select("so.so_no", "so.biz_type", "so.amount", "so.currency_id", "so.platform_order_id", "so.txn_id", "pg.status AS payment_gateway_status", "pg.id AS payment_gateway", "pg.settlement_date_type", "pg.settlement_date_day", "so.order_create_date", "so.dispatch_date", \DB::Raw("DATE_ADD(so.$dateField,INTERVAL pg.settlement_date_day DAY) AS estimated_date"))
                ->get();
        return $result;
    }

    public function getLastMonday()
    {
        if (date('l', time()) == 'Monday') {
            return date('Y-m-d', strtotime('last monday'));
        }
        return date('Y-m-d', strtotime('-1 week last monday'));
    }

    public function getLastSunday()
    {
        return date('Y-m-d', strtotime('last sunday'));
    }

    public function calculateEstimatedAmount($soObj)
    {
        $feeAmount = 0;
        $salesOrderStatistics = $soObj->salesOrderStatistic;

        foreach ($salesOrderStatistics as $row) {
            $feeAmount = $row->marketplace_list_fee
                            + $row->marketplace_fixed_fee
                            + $row->psp_admin_fee
                            + $row->marketplace_commission
                            + $row->payment_gateway_fee
                            + $row->fulfilment_by_marketplace_fee;
            if ($soObj->biz_type == "Lazada") {
                $feeAmount += $row->shipping_cost;
            }
        }

        $estimatedAmount = $feeAmount == 0 ? 0 : $soObj->amount - $feeAmount;
        return $estimatedAmount;
    }
}
