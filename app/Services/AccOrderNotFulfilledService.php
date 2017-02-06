<?php

namespace App\Services;

use App\Models\So;

class AccOrderNotFulfilledService
{
    use BaseMailService;

    public function sendAccOrderNotFulfilledAlert()
    {

        $orders = $this->getAccOrderNotFulfilledReport();
        if ($cellDatas = $this->getAccOrderNotFulfilledCellDatas($orders)) {
            foreach ($cellDatas as $email => $cellData) {
                $this->sendNotFulfilledEmail($email, $cellData);
            }
        }

        $cellAllDatas = $this->getAccOrderNotFulfilledCellAllDatas($orders);
        $this->sendNotFulfilledEmail("privatelabel-log@eservicesgroup.com, dispatcher@eservicesgroup.com, storemanager@brandsconnect.net", $cellAllDatas);
    }

    private function sendNotFulfilledEmail($toMail, $cellData)
    {
        $filePath = \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."NotFulfilled/";
        $fileName = "Accelerator_Order_Not_Fulfilled_".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "[ESG] Alert, Accelerator & TRANSFER orders are not fulfilled within 24 hours, Please cehck report!";
                $attachment = [
                    "path" => $orderPath,
                    "file_name"=>$fileName .".xlsx"
                ];
                $this->sendAttachmentMail(
                    $toMail,
                    $subject,
                    $attachment
                );
            }
        }
    }

    public function getAccOrderNotFulfilledReport()
    {
        return $this->getAccOrderNotFulfilledData();
    }

    public function getAccOrderNotFulfilledCellDatas($orders)
    {
        if (! $orders->isEmpty()) {
            $cellDatas = [];
            foreach ($orders as $order) {
                if (! isset($cellDatas[$order->email])) {
                    $cellDatas[$order->email][] = [
                        'Order NO.',
                        'Order Create Date',
                    ];
                }
                $cellDatas[$order->email][] = [
                    'so_no' => $order->so_no,
                    'order_create_date' => $order->order_create_date,
                ];
            }
            return $cellDatas;
        }
    }

    public function getAccOrderNotFulfilledCellAllDatas($orders)
    {
        if (! $orders->isEmpty()) {
            $cellDatas = [];
            $cellDatas[] = [
                'Order NO.',
                'Order Create Date',
                'Create By User',
            ];
            foreach ($orders as $order) {
                $cellDatas[$order->email][] = [
                    'so_no' => $order->so_no,
                    'order_create_date' => $order->order_create_date,
                    'username' => $order->username,
                ];
            }
            return $cellDatas;
        }
    }

    public function getAccOrderNotFulfilledData()
    {
        return SO::join("selling_platform AS sp", "so.platform_id", "=", "sp.id")
        ->Join("user AS u", "u.id", "=", "so.create_by")
        ->where("so.status", ">", '2')
        ->where("so.status", "<", '6')
        ->whereIn("sp.type", ['ACCELERATOR', 'TRANSFER'])
        ->where("so.platform_group_order", '1')
        ->where("so.refund_status", '0')
        ->where("so.hold_status", "!=", '10')
        ->where("so.order_create_date", '>=', '2016-04-01')
        ->where("so.order_create_date", '<=', \DB::raw("(Now()-interval 1 day)"))
        ->where("so.create_by", '!=', 'system')
        ->groupBy("so.so_no")
        ->orderBy(\DB::raw("u.email, so.order_create_date, so.so_no"))
        ->select(\DB::raw("so.so_no, so.order_create_date, u.email, u.username"))
        ->get();
    }
}
