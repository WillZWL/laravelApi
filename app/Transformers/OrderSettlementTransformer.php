<?php

namespace App\Transformers;

use App\Models\So;
use App\Services\SettlementPreviewService;
use League\Fractal\TransformerAbstract;

class OrderSettlementTransformer extends TransformerAbstract
{
    public function transform(So $order)
    {
        $estimated_settlement_date = $this->getEstimatedSettlementDate($order);
        $estimated_settlement_amount = $this->getEstimatedSettlementAmount($order);
        return [
            'payment_gateway_id' => $order->payment_gateway_id,
            'biz_type' => $order->biz_type,
            'txn_id' => $order->txn_id,
            'so_no' => $order->so_no,
            'platform_order_id' => $order->platform_order_id,
            'order_create_date' => $order->order_create_date,
            'dispatch_date' => $order->dispatch_date,
            'currency_id' => $order->currency_id,
            'amount' => $order->amount,
            'settlement_date' => $order->settlement_date,
            'estimated_settlement_amount' => $estimated_settlement_amount,
            'estimated_settlement_date' => $estimated_settlement_date,
            'marketplace_contact_name' => $order->marketplace_contact_name,
            'marketplace_contact_phone' => $order->marketplace_contact_phone,
            'marketplace_email' => trim($order->marketplace_email_1." ".$order->marketplace_email_2." ".$order->marketplace_email_3)
        ];
    }

    public function getEstimatedSettlementDate($order)
    {
        $day = $order->settlement_date_day;
        $estimated_settlement_date = '';
        switch ($order->settlement_date_type) {
            case 'order_create_date':
                $estimated_settlement_date = date('Y-m-d', strtotime($order->order_create_date." +".$day." days"));
                break;
            case 'create_on':
                $estimated_settlement_date = date('Y-m-d', strtotime($order->create_on." +".$day." days"));
                break;
            case 'shipped_date':
                $estimated_settlement_date = date('Y-m-d', strtotime($order->dispatch_date." +".$day." days"));
                break;
            default:
                $estimated_settlement_date = date('Y-m-d', strtotime($order->order_create_date." +".$day." days"));
                break;
        }
        return $estimated_settlement_date;
    }

    //TODO
    public function getEstimatedSettlementAmount($order)
    {
        $settlementPreviewService = new SettlementPreviewService();
        return $settlementPreviewService->calculateEstimatedAmount($order);
    }
}
