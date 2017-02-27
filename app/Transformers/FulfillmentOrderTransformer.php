<?php

namespace App\Transformers;

use App\Models\So;
use App\Models\SoItem;
use App\Models\ProductAssemblyMapping;
use App\Models\CourierInfo;
use League\Fractal\TransformerAbstract;
use Cache;

class FulfillmentOrderTransformer extends TransformerAbstract
{
    public function transform(So $order)
    {
        $prodAssemblyMainSkus = $this->getAssemblyMapping();
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
            'reference_no' => $order->so_no,
            'marketplace_reference_no' => $order->platform_order_id,
            'marketplace_platform_id' => $order->platform_id,
            'merchant_id' => 'ESG',
            'sub_merchant_id' => $order->sellingPlatform->merchant_id,
            'order_type' => $order->sellingPlatform->type,
            'biz_type' => $order->biz_type,
            'order_create_date' => $order->order_create_date,
            'recommend_courier_id' => $order->recommend_courier_id,
            'recommend_courier_name' => $this->getCourierNameById($order->recommend_courier_id),
            'allocation_warehouse' => $this->getAllocationWarehouse($order),
            'delivery_name' => $order->delivery_name,
            'address' => $order->delivery_address,
            'city' =>  $order->delivery_city,
            'state' => $order->delivery_state,
            'country' => $order->delivery_country_id,
            'postcode' => $order->delivery_postcode,
            'phone' => trim($order->del_tel_1." ".$order->del_tel_2." ".$order->del_tel_3),
            'currency' => $order->currency_id,
            'delivery_charge' => $order->delivery_charge,
            'amount' => $order->amount,
            'status' => $order->status,
            'feed_status' => $order->dnote_invoice_status,
            'items' => $orderItems
        ];
    }

    private function getAssemblyMapping()
    {
        return Cache::get('prodAssemblyMainSkus', function() {
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
            Cache::add('prodAssemblyMainSkus', $prodAssemblyMainSkus, 60);
        });
    }

    //TODO
    private function getAllocationWarehouse($order)
    {
        $warehouse = 'WMS';
        if ($order->sellingPlatform->merchant_id == 'PREPD') {
            $warehouse = '4PX_B66';
        }
        return $warehouse;
    }

    private function getCouriers()
    {
        return Cache::get('couriers', function() {
            $couriers = CourierInfo::all();
            foreach ($couriers as $courier) {
                $couriersArr[$courier->courier_id] = $courier->courier_name;
            }
            Cache::add('couriers', $couriersArr, 60*24);
        });
    }

    private function getCourierNameById($id = '')
    {
        $courierName = '';
        $couriersArr = $this->getCouriers();
        if (array_key_exists($id, $couriersArr)) {
            $courierName = $couriersArr[$id];
        }
        return $courierName;
    }
}
