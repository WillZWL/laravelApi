<?php

namespace App\Transformers;

use App\Models\PlatformMarketOrder;
use App\Models\ProductAssemblyMapping;
use App\Models\PlatformMarketInventory;
use League\Fractal\TransformerAbstract;

class PlatformOrderTransformer extends TransformerAbstract
{
    public function transform(PlatformMarketOrder $order)
    {
        //get ordet item ['sku', 'qty', 'inventory']
        $merchant = 'MATTEL';
        $items = [];
        foreach ($order->platformMarketOrderItem as $orderItem) {
            $esgSku = $orderItem->marketplaceSkuMapping()->first()->sku;
            $marketplaceId = $orderItem->marketplaceSkuMapping()->first()->marketplace_id;

            $assemblyMappings = ProductAssemblyMapping::active()->where('main_sku', $esgSku)
                ->where('status', '=', '1')
                ->get();

            if (!$assemblyMappings->isEmpty()) {
                //replace Assembly Sku
                foreach ($assemblyMappings as $assemblySku) {
                    $item['sku'] = $assemblySku->merchantProductMapping()
                                               ->where('merchant_id', $merchant)
                                               ->first()
                                               ->merchant_sku;
                    $item['qty'] = $orderItem->quantity_ordered * $assemblySku->replace_qty;

                    $inventory = PlatformMarketInventory::where('mattel_sku', $item['sku'])->where('store_id', $order->store_id)->first();
                    $item['inventory'] = 0;
                    if ($inventory) {
                        $item['inventory'] = $inventory->inventory;
                    }
                    $items[] = $item;
                }
            } else {
                $item['sku'] = $orderItem->seller_sku;
                $item['qty'] = $orderItem->quantity_ordered;
                $inventory = $orderItem->platformMarketInventory()
                                       ->where('store_id', $order->store_id)
                                       ->first();
                $item['inventory'] = 0;
                if ($inventory) {
                    $item['inventory'] = $inventory->inventory;
                }
                $items[] = $item;
            }
        };

        return [
            'id' => $order->id,
            'store_id' => $order->store_id,
            'biz_type' => $order->biz_type,
            'merchant' => $merchant,
            'platform_order_id' => $order->platform_order_no,
            'order_create_date' => $order->purchase_date,
            'payment_method' => $order->payment_method,
            'platform' => $order->platform,
            'items' => $items
        ];
    }
}
