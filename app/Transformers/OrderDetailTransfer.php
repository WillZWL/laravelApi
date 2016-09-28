<?php

namespace App\Transformers;

use App\Models\PlatformMarketOrder;
use League\Fractal\TransformerAbstract;

class OrderDetailTransformer extends TransformerAbstract
{
    public function transform(PlatformMarketOrder $order)
    {
        return [
            'payment_method' => $order->payment_method,
            'purchase_date' => $order->purchase_date,
            'items' => $order->platformMarketOrderItem,
            'bill_name' => $order->platformMarketShippingAddress->bill_name,
            'bill_address_line_1' => $order->platformMarketShippingAddress->bill_address_line_1,
            'bill_address_line_2' => $order->platformMarketShippingAddress->bill_address_line_2,
            'bill_address_line_3' => $order->platformMarketShippingAddress->bill_address_line_3,
            'bill_city' => $order->platformMarketShippingAddress->bill_city,
            'bill_county' => $order->platformMarketShippingAddress->bill_county,
            'bill_district' => $order->platformMarketShippingAddress->bill_district,
            'bill_postal_code' => $order->platformMarketShippingAddress->bill_postal_code,
            'bill_phone' => $order->platformMarketShippingAddress->bill_phone,
            'name' => $order->platformMarketShippingAddress->name,
            'address_line_1' => $order->platformMarketShippingAddress->address_line_1,
            'address_line_2' => $order->platformMarketShippingAddress->address_line_2,
            'address_line_3' => $order->platformMarketShippingAddress->address_line_3,
            'county' => $order->platformMarketShippingAddress->county,
            'city' => $order->platformMarketShippingAddress->city,
            'district' => $order->platformMarketShippingAddress->district,
            'postal_code' => $order->platformMarketShippingAddress->postal_code,
            'phone' => $order->platformMarketShippingAddress->phone,
        ];
    }
}
