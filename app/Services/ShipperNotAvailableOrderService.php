<?php

namespace App\Services;

use App\Models\So;
use App\Models\SoHoldReason;

class ShipperNotAvailableOrderService
{
    use BaseMailService;

    private $newSoHoldReason = null;

    public function processOrder()
    {

        $this->getData();
    }

    public function getData()
    {
        $orders = $this->getShipperNotAvailableOrders();
        if (! $orders->isEmpty()) {
            foreach ($orders as $order) {
                if ($order->status > 0 && $order->status < 6) {
                    \DB::connection('mysql_esg')->beginTransaction();
                    try {
                        $reason = "Shipper Not Available";
                        $this->saveSoHoldReason($order->so_no, $reason);
                        $order->hold_status = 4;
                        $order->save();
                        \DB::connection('mysql_esg')->commit();
                    } catch (\Exception $e) {
                        \DB::connection('mysql_esg')->rollBack();
                        echo $e->getMessage(). ", Line: ".$e->getLine();
                    }
                }
            }
        }
    }

    public function saveSoHoldReason($soNo, $reason)
    {
        $newSoHoldReason = $this->getNewSoHoldReason();
        $soHoldReason = clone $newSoHoldReason;
        $soHoldReason->so_no = $soNo;
        $soHoldReason->reason = $reason;
        $soHoldReason->save();
    }

    public function getShipperNotAvailableOrders()
    {
        return So::leftJoin('courier_info AS ci', 'ci.courier_id', '=', 'so.esg_quotation_courier_id')
            ->where('so.platform_group_order', 1)
            ->where('so.status', 3)
            ->where('so.refund_status', 0)
            ->where('so.hold_status', 0)
            ->where('so.prepay_hold_status', 0)
            ->where('so.platform_id', 'NOT LIKE', 'EXCV%')
            ->where(function ($query) {
                $query->whereNull('so.esg_quotation_courier_id')
                ->orWhere('so.esg_quotation_courier_id', '=', '')
                ->orWhere('ci.status', '0');
            })
            ->get([
                'so.so_no',
                'so.order_create_date',
                'so.status',
                'ci.courier_id',
                'ci.courier_name',
                'ci.status AS courier_status'
            ]);
    }

    public function getNewSoHoldReason()
    {
        if ($this->newSoHoldReason === null) {
            $this->newSoHoldReason = new SoHoldReason();
        }
        return $this->newSoHoldReason;
    }

}