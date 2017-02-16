<?php

namespace App\Transformers;

use App\Models\So;
use App\Models\SoItem;
use App\Models\ProductAssemblyMapping;
use League\Fractal\TransformerAbstract;

class FulfillmentOrderTransformer extends TransformerAbstract
{
    public function transform(So $order)
    {
        $assemblyMappings = ProductAssemblyMapping::active()->whereIsReplaceMainSku('1')->get();
        $prodAssemblyMainSkus = [];
        if (! $assemblyMappings->isEmpty() ) {
            foreach ($assemblyMappings as $assemblyMapping) {
                $prodAssemblyMainSkus[] = $assemblyMapping->main_sku;
            }
        }
        $orderItems = [];
        if (!$order->soItem->isEmpty()) {
            $items = [];
            foreach ($order->soItem as $soItem) {
                if (in_array($soItem->prod_sku, $prodAssemblyMainSkus)) {
                    $assemblySkus = ProductAssemblyMapping::active()->where('main_sku', $soItem->prod_sku)->get();
                    foreach ($assemblySkus as $assemblySku) {
                        if (isset($items[$assemblySku->sku])) {
                            $items[$assemblySku->sku] += $soItem->qty * $assemblySku->replace_qty;
                        } else {
                            $items[$assemblySku->sku] = $soItem->qty * $assemblySku->replace_qty;
                        }
                    }
                } else {
                    if (isset($items[$soItem->prod_sku])) {
                        $items[$soItem->prod_sku] += $soItem->qty;
                    } else {
                        $items[$soItem->prod_sku] = $soItem->qty;
                    }
                }
            }
            foreach ($items as $key => $value) {
                $orderItem['sku'] = $key;
                $orderItem['quantity'] = $value;
                $orderItems[] = $orderItem;
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
            'currency' => $order->currency_id,
            'delivery_charge' => $order->delivery_charge,
            'amount' => $order->amount,
            'status' => $order->status,
            'items' => $orderItems
        ];
    }
}