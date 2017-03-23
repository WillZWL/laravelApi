<?php

namespace App\Transformers;

use App\Models\So;
use App\Models\SoItem;
use App\Models\ProductAssemblyMapping;
use App\Services\CourierInfoService;
use League\Fractal\TransformerAbstract;
use Cache;

class FulfillmentOrderTransformer extends TransformerAbstract
{
    public function transform(So $order)
    {
        $prodAssemblyMainSkus = $this->getAssemblyMapping();
        $merchant = $order->sellingPlatform->merchant;
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
                $orderItem['default_iwms_warehouse_code'] = $soid->product->default_ship_to_warehosue;
                $orderItem['outstanding_qty'] = $outstandingQty;
                $orderItem['sku'] = $itemSku;
                $orderItems[] = $orderItem;
            }
        }
        
        return [
            'order_no' => $order->so_no,
            'reference_no' => $order->so_no,
            'marketplace_reference_no' => $order->platform_order_id,
            'marketplace_platform_id' => $order->platform_id,
            'merchant_id' => $this->getIwmsMerchantId($esgMerchantId),
            'sub_merchant_id' => $merchant->id,
            'order_type' => $order->sellingPlatform->type,
            'merchant_iwms_warehouse_code' => $merchant->default_ship_to_warehouse,
            'biz_type' => $order->biz_type,
            'order_create_date' => date('Y-m-d', strtotime($order->order_create_date)),
            'courier_id' => $order->esg_quotation_courier_id,
            'courier_name' => $this->getCourierNameById($order->esg_quotation_courier_id),
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
            'amount' => number_format($order->amount, 2, '.', ''),
            'status' => $order->status,
            'refund_status' => $order->refund_status,
            'hold_status' => $order->hold_status,
            'merchant_hold_status' => $order->merchant_hold_status,
            'prepay_hold_status' => $order->prepay_hold_status,
            'feed_status' => $order->dnote_invoice_status,
            'pick_list_no' => $order->pick_list_no,
            'items' => $orderItems
        ];
    }

    private function getIwmsMerchantId($esgMerchantId)
    {
        /*if(in_array($esgMerchantId, ["RONNEXT","RING","TWINSYNERGY","LUMOS"])){
            return "ESG-HK-TEST";
        }else{
            return "ESG";
        }*/
        return "ESG";
    }

    private function getAssemblyMapping()
    {
        return Cache::store('file')->get('prodAssemblyMainSkus', function () {
            $assemblyMappings = ProductAssemblyMapping::active()->whereIsReplaceMainSku('1')->get();
            $prodAssemblyMainSkus = [];
            if (! $assemblyMappings->isEmpty()) {
                foreach ($assemblyMappings as $assemblyMapping) {
                    $prodAssemblyMainSkus[$assemblyMapping->main_sku] = [
                        'sku' => $assemblyMapping->sku,
                        'replace_qty' => $assemblyMapping->replace_qty,
                    ];
                }
            }
            Cache::store('file')->add('prodAssemblyMainSkus', $prodAssemblyMainSkus, 60*24);
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

    public function getCourierNameById($id)
    {
        $courierService = new CourierInfoService();

        return $courierService->getCourierNameById($id);
    }
}
