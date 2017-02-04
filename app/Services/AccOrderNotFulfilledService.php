<?php

namespace App\Services;

use App\Models\So;

class AccOrderNotFulfilledService
{
    use BaseMailService;

    public function sendAccOrderNotFulfilledAlert()
    {
        $cellDatas = $this->getAccOrderNotFulfilledReport();
        if ($cellDatas) {
            foreach ($cellDatas as $email => $cellData) {
                $this->sendNotFulfilledEmail($email, $cellData);
            }
        }
    }

    private function sendNotFulfilledEmail($toMail, $cellData)
    {
        $filePath = \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."NotFulfilled/";
        $fileName = "Accelerator_Order_Not_Fulfilled_".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "[ESG] Alert, Accelerator orders are not fulfilled within 24 hours, Please cehck report!";
                $attachment = [
                    "path" => $orderPath,
                    "file_name"=>$fileName .".xlsx"
                ];
                $this->sendAttachmentMail(
                    $toMail,
                    $subject,
                    $attachment,
                    "privatelabel-log@eservicesgroup.com, dispatcher@eservicesgroup.com"
                );
            }
        }
    }

    public function getAccOrderNotFulfilledReport()
    {
        $orders = $this->getAccOrderNotFulfilledData();
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

    public function getAccOrderNotFulfilledData()
    {
        return SO::join("selling_platform AS sp", "so.platform_id", "=", "sp.id")
        ->Join("user AS u", "u.id", "=", "so.create_by")
        ->where("so.status", ">", '2')
        ->where("so.status", "<", '6')
        ->where("sp.type", 'ACCELERATOR')
        ->where("so.platform_group_order", '1')
        ->where("so.refund_status", '0')
        ->where("so.hold_status", "!=", '10')
        ->where("so.order_create_date", '>=', '2016-04-01')
        ->where("so.order_create_date", '<=', \DB::raw("(Now()-interval 1 day)"))
        ->where("so.create_by", '!=', 'system')
        ->groupBy("so.so_no")
        ->orderBy(\DB::raw("u.email, so.order_create_date, so.so_no"))
        ->select(\DB::raw("so.so_no, so.order_create_date, u.email"))
        ->get();
    }
}
