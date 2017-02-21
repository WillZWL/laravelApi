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
                $prodAssemblyMainSkus[$assemblyMapping->main_sku] = [
                    'sku' => $assemblyMapping->sku,
                    'replace_qty' => $assemblyMapping->replace_qty,
                ];
            }
        }

        $orderItems = [];
        if (! $order->soItemDetail->isEmpty()) {
            foreach ($order->soItemDetail as $soid) {
                $lineNo = $soid->line_no;
                $itemSku = $soid->item_sku;
                $outstandingQty = $soid->outstanding_qty;
                $qty = $soid->qty;
                if (isset($prodAssemblyMainSkus[$itemSku])) {
                    $prodAssemb = $prodAssemblyMainSkus[$itemSku];
                    $itemSku = $prodAssemb['sku'];
                    $outstandingQty = $soid->outstanding_qty * $prodAssemb['replace_qty'];
                    $qty = $soid->qty * $prodAssemb['replace_qty'];
                }
                $orderItem['line_no'] = $lineNo;
                $orderItem['sku'] = $itemSku;
                $orderItem['quantity'] = $qty;
                $orderItem['outstanding_qty'] = $outstandingQty;
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