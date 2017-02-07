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
                        'Business Type',
                        'Platform ID',
                        'Order NO.',
                        'Order Status',
                        'Hold Status',
                        'Order Created Date',
                        'Created By User',
                        'Last Updated Date',
                        'Last Updated By User',
                    ];
                }
                $cellDatas[$order->email][] = [
                    'type' => $order->type,
                    'platform_id' => $order->platform_id,
                    'so_no' => $order->so_no,
                    'status' => $order->status,
                    'hold_status' => $order->hold_status,
                    'order_create_date' => $order->create_date,
                    'username' => $order->c_username,
                    'modify_on' => $order->modify_on,
                    'm_username' => $order->m_username,
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
                'Business Type',
                'Platform ID',
                'Order NO.',
                'Order Status',
                'Hold Status',
                'Order Created Date',
                'Created By User',
                'Last Updated Date',
                'Last Updated By User',
            ];
            foreach ($orders as $order) {
                $cellDatas[] = [
                    'type' => $order->type,
                    'platform_id' => $order->platform_id,
                    'so_no' => $order->so_no,
                    'status' => $order->status,
                    'hold_status' => $order->hold_status,
                    'order_create_date' => $order->create_date,
                    'username' => $order->c_username,
                    'modify_on' => $order->modify_on,
                    'm_username' => $order->m_username,
                ];
            }
            return $cellDatas;
        }
    }

    public function getAccOrderNotFulfilledData()
    {
        return SO::join("selling_platform AS sp", "so.platform_id", "=", "sp.id")
            ->Join("user AS u", "u.id", "=", "so.create_by")
            ->leftJoin("user AS ur", "ur.id", "=", "so.modify_by")
            ->where("so.status", ">", '2')
            ->where("so.status", "<", '6')
            ->where("sp.type", 'TRANSFER')
            ->whereNotIn("so.so_no", ['276585, 276624, 276626, 276630'])
            ->where("so.platform_group_order", '1')
            ->where("so.refund_status", '0')
            ->where("so.hold_status", "!=", '10')
            ->where("so.order_create_date", '>=', '2016-04-01')
            ->where("so.order_create_date", '<=', \DB::raw("(Now()-interval 1 day)"))
            ->where("so.create_by", '!=', 'system')
            ->groupBy("so.so_no")
            ->orderBy(\DB::raw("u.email, so.order_create_date, so.so_no"))
            ->select(
                \DB::raw("
                    so.platform_id,
                    sp.type,
                    so.so_no,
                    ifnull(so.order_create_date, so.create_on) create_date,
                    u.email,
                    u.username c_username,
                    so.status,
                    so.hold_status,
                    so.modify_on,
                    ur.username m_username
                ")
            )
            ->get();
    }
}
