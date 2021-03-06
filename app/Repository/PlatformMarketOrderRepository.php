<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\PlatformMarketOrder;
use App\Models\Store;
use App\Models\MarketplaceSkuMapping;
use App\Models\StoreWarehouse;
use App\Models\MattelSkuMapping;

class PlatformMarketOrderRepository
{
    public function getOrdersByStore(Request $request, Array $stores = [])
    {
        $query = PlatformMarketOrder::with('platformMarketOrderItem')
            ->whereIn('platform_market_order.store_id', $stores);

        switch ($request->get('status')) {
            case 'new':
                $query = $query->whereIn('platform_market_order.esg_order_status', [1, 2, 3, 4, 13, 14]);
                break;

            case 'ready':
                $query = $query->where('platform_market_order.esg_order_status', '=', 5);
                break;

            case 'shipped':
                $query = $query->where('platform_market_order.esg_order_status', '=', 6);
                break;
        }

        return $query->paginate(30);
    }

    public function getOrderDetails($id)
    {
        return PlatformMarketOrder::with('platformMarketOrderItem')
            ->with('platformMarketShippingAddress')
            ->where('id', $id)->get();
    }

    public function getMattelDcSkuMappingOrderItems($platformMarketOrder)
    {
        $store = Store::find($platformMarketOrder->store_id);
        $marketplaceId = $store->store_name.$store->marketplace;
        $storeWarehouse = StoreWarehouse::where('store_id',$platformMarketOrder->store_id)->first();
        foreach ($platformMarketOrder->platformMarketOrderItem as $key => $orderItem) {
            $marketplaceSkuMapping = MarketplaceSkuMapping::where("marketplace_sku","=",$orderItem->seller_sku)
                        ->where("marketplace_id","=",$marketplaceId)
                        ->where("country_id","=",$store->country)
                        ->with('merchantProductMapping')
                        ->first();
            $mattelSkuMapping = MattelSkuMapping::where("mattel_sku",$marketplaceSkuMapping->merchantProductMapping->merchant_sku)
                     ->where('warehouse_id',$storeWarehouse->warehouse_id)
                     ->first();
            $orderItem->platform_order_id = $platformMarketOrder->platform_order_no;
            $orderItem->seller_sku = $mattelSkuMapping->dc_sku;
        }
    }
}
