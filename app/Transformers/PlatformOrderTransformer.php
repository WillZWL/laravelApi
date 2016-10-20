<?php

namespace App\Transformers;

use App\Models\PlatformMarketOrder;
use League\Fractal\TransformerAbstract;

class PlatformOrderTransformer extends TransformerAbstract
{
    public function transform(PlatformMarketOrder $order)
    {
        return [
            'id' => $order->id,
            'biz_type' => $order->biz_type,
            'merchant' => 'MATTEL',
            'platform_order_id' => $order->platform_order_no,
            'order_create_date' => $order->purchase_date,
            'payment_method' => $order->payment_method,
            'platform' => $order->platform,
            'items' => $order->platformMarketOrderItem->pluck('quantity_ordered', 'seller_sku'),
        ];
    }
}
