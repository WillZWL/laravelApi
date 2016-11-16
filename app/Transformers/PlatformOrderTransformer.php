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
            $marketplaceSkuMapping = $orderItem->marketplaceSkuMapping()->first()->sku;
            if(!empty($marketplaceSkuMapping)){
                $assemblyMappings = ProductAssemblyMapping::active()
                        ->where('main_sku', $marketplaceSkuMapping->sku)
                        ->where('status', '=', '1')
                        ->get();
                $marketplaceId = $marketplaceSkuMapping->marketplace_id;       
            }
            if (!$assemblyMappings->isEmpty()) {
                //replace Assembly Sku
                foreach ($assemblyMappings as $assemblySku) {
                    $merchantProductMapping = $assemblySku->merchantProductMapping($merchant)->first();
                    if(!empty($merchantProductMapping)){
                       $item['sku'] = $merchantProductMapping->merchant_sku; 
                    }
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
                $inventory = $orderItem->platformMarketInventory($order->store_id)->first();
                $item['inventory'] = 0;
                if ($inventory) {
                    $item['inventory'] = $inventory->inventory;
                }
                $items[] = $item;
            }
        };
        
        return [
            'id' => $order->id,
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
