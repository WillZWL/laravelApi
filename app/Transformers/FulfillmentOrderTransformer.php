<?php

namespace App\Transformers;

use App\Models\So;
use App\Models\SoItem;
use League\Fractal\TransformerAbstract;

class FulfillmentOrderTransformer extends TransformerAbstract
{
    public function transform(So $order)
    {
        $items = [];
        if (!$order->soItem->isEmpty()) {
            foreach ($order->soItem as $soItem) {
                $item = [
                    'line_no' => $soItem->line_no,
                    'sku' => $soItem->prod_sku,
                    'quantity' => $soItem->qty,
                    'unit_price' => $soItem->unit_price,
                    'item_amount' => $soItem->amount,
                ];
                $items[] = $item;
            }
        }
        return [
            'order_no' => $order->so_no,
            'platform_id' => $order->platform_id,
            'retailer_name' => 'ESG',
            'order_type' => $order->sellingPlatform->type,
            'biz_type' => $order->biz_type,
            'order_create_date' => $order->order_create_date,
            'delivery_info' => [
                'name' => $order->delivery_name,
                'address' => $order->delivery_address,
                'postcode' => $order->delivery_postcode,
                'city' =>  $order->delivery_city,
                'state' => $order->delivery_state,
                'country' => $order->delivery_country_id
            ],
            'order_score' => 100,
            'currency' => $order->currency_id,
            'delivery_charge' => $order->delivery_charge,
            'amount' => $order->amount,
            'status' => $order->status,
            'items' => $items
        ];
    }
}