<?php

namespace App\Services;

use App\Models\So;

class AccOrderNotFulfilledService
{
    use BaseMailService;

    public function sendAccOrderNotFulfilledAlert()
    {
        $this->sendAccOrderNotAllocatedAlert();

        $this->sendAccOrderNotShippedAlert();
    }

    public function sendAccOrderNotAllocatedAlert($value='')
    {
        $cellData = $this->getAccOrderNotAllocatedReport();
        $filePath = \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."notAllocated/";
        $fileName = "AccOrderNotAllocated-".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "[ESG] Alert, These accelerator order created more than 24 hours not allocated report!";
                $attachment = [
                    "path" => $orderPath,
                    "file_name"=>$fileName .".xlsx"
                ];
                $this->sendAttachmentMail(
                    "privatelabel-log@eservicesgroup.com, dispatcher@eservicesgroup.com",
                    $subject,
                    $attachment,
                    "brave.liu@eservicesgroup.com"
                );
            }
        }
    }

    public function sendAccOrderNotShippedAlert()
    {
        $cellData = $this->getAccOrderNotShippedReport();
        $filePath = \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $orderPath = $filePath."notShipped/";
        $fileName = "AccOrderNotShipped-".time();
        if(!empty($cellData)){
            $excelFile = $this->createExcelFile($fileName, $orderPath, $cellData);
            if($excelFile){
                $subject = "[ESG] Alert, These accelerator order not shipped for not fulfilled within 24 hours report!";
                $attachment = [
                    "path" => $orderPath,
                    "file_name"=>$fileName .".xlsx"
                ];
                $this->sendAttachmentMail(
                    "privatelabel-log@eservicesgroup.com, dispatcher@eservicesgroup.com",
                    $subject,
                    $attachment,
                    "brave.liu@eservicesgroup.com"
                );
            }
        }
    }

    public function getAccOrderNotAllocatedReport()
    {
        $orders = $this->getAccOrderNotAllocatedData();
        if (! $orders->isEmpty()) {
            $cellData[] = [
                'Order NO.',
                'Order Create Date',
                'Lack Inventory Records',
            ];
            foreach ($orders as $order) {
                $lack_records = $order->lack_inventory_records ? "Y" : "N";
                $cellData[] = [
                    'so_no' => $order->so_no,
                    'order_create_date' => $order->order_create_date,
                    'lack_records' => $lack_records,
                ];
            }
            return $cellData;
        }
    }

    public function getAccOrderNotShippedReport()
    {
        $orders = $this->getAccOrderNotShippedData();
        if (! $orders->isEmpty()) {
            $cellData[] = [
                'Order NO.',
                'Order Create Date',
            ];
            foreach ($orders as $order) {
                $cellData[] = [
                    'so_no' => $order->so_no,
                    'order_create_date' => $order->order_create_date,
                ];
            }
            return $cellData;
        }
    }

    public function getAccOrderNotAllocatedData()
    {
        return SO::join("selling_platform AS sp", "so.platform_id", "=", "sp.id")
        ->join("so_item AS soi", "soi.so_no", "=", "so.so_no")
        ->leftJoin("inventory AS inv", function ($join) {
            $join->on("soi.prod_sku", "=", "inv.prod_sku")
            ->whereNotNull("inv.prod_sku");
        })
        ->where("so.status", ">", '2')
        ->where("so.status", "<", '5')
        ->where("so.refund_status", '0')
        ->where("so.hold_status", '0')
        ->where("sp.type", 'ACCELERATOR')
        ->where("so.platform_group_order", '1')
        ->where("so.prepay_hold_status", '0')
        ->where("so.merchant_hold_status", '0')
        ->where("so.order_create_date", '<=', \DB::raw("(Now()-interval 1 day)"))
        ->groupBy("so_no")
        ->orderBy(\DB::raw("if(inv.prod_sku,0, 1), so_no, order_create_date"))
        ->select(\DB::raw("so.so_no, so.order_create_date, if(inv.prod_sku,0, 1) lack_inventory_records"))
        ->get();
    }

    public function getAccOrderNotShippedData()
    {
        return SO::join("selling_platform AS sp", "so.platform_id", "=", "sp.id")
        ->join("so_allocate AS soal", "so.so_no", "=", "soal.so_no")
        ->join("so_shipment AS sosh", "soal.sh_no", "=", "sosh.sh_no")
        ->where("so.status", ">", '3')
        ->where("so.status", "<", '6')
        ->where("soal.status", "=", '2')
        ->where("sp.type", 'ACCELERATOR')
        ->where("so.platform_group_order", '1')
        ->where("so.prepay_hold_status", '0')
        ->where("so.order_create_date", '<=', \DB::raw("(Now()-interval 1 day)"))
        ->groupBy("so.so_no")
        ->orderBy(\DB::raw("so.so_no, so.order_create_date"))
        ->select(\DB::raw("so.so_no, so.order_create_date"))
        ->get();
    }
}
