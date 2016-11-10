<?php

namespace App\Transformers;

use App\Models\PlatformMarketOrder;
use League\Fractal\TransformerAbstract;

class PlatformOrderTransformer extends TransformerAbstract
{
    public function transform(PlatformMarketOrder $order)
    {
        $items = [];
        foreach ($order->platformMarketOrderItem as $orderItem) {
            $item['sku'] = $orderItem->seller_sku;
            $item['qty'] = $orderItem->quantity_ordered;
            $inventory = $orderItem->platformMarketInventory($order->store_id)->first();
            $item['inventory'] = 0;
            if ($inventory) {
                $item['inventory'] = $inventory->inventory;
            }
            $items[] = $item;
        };
        return [
            'id' => $order->id,
            'biz_type' => $order->biz_type,
            'merchant' => 'MATTEL',
            'platform_order_id' => $order->platform_order_no,
            'order_create_date' => $order->purchase_date,
            'payment_method' => $order->payment_method,
            'platform' => $order->platform,
            'items' => $items
        ];
    }
}
