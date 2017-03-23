<?php

namespace App\Transformers;

use App\Models\So;
use App\Services\SettlementPreviewService;
use League\Fractal\TransformerAbstract;

class OrderSettlementTransformer extends TransformerAbstract
{
    use \App\Services\TraitDeclaredService;

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
            'validation_status' => $order->validation_status,
            'estimated_settlement_amount' => $estimated_settlement_amount,
            'estimated_settlement_date' => $estimated_settlement_date,
            'marketplace_contact_name' => $order->marketplace_contact_name,
            'marketplace_contact_phone' => $order->marketplace_contact_phone,
            'marketplace_email_1' => trim($order->marketplace_email_1),
            'marketplace_email_2' => trim($order->marketplace_email_2),
            'marketplace_email_3' => trim($order->marketplace_email_3)
        ];
    }

    public function getEstimatedSettlementAmount($order)
    {
        $settlementPreviewService = new SettlementPreviewService();
        return $settlementPreviewService->calculateEstimatedAmount($order);
    }
}
