<?php

namespace App\Repository;

use App\Models\AcceleratorShipping;
use App\Models\CourierCost;
use App\Models\Product;
use App\Models\MerchantProductMapping;
use App\Models\DeliveryTypeMapping;


class AcceleratorShippingRepository
{
    public function shippingOptions($merchantId = 'CASE', $warehouse = 'ES_HK', $deliveryCountry = 'US')
    {
        // TODO
        // need to confirm use merchant ID or 'ALL'
        $merchantId = 'ALL';
        return AcceleratorShipping::where('warehouse', '=', $warehouse)
            ->where('country_id', '=', $deliveryCountry)
            ->where('merchant_id', '=', $merchantId)
            ->where('status', 1)
            ->get();
    }

    public function getShipToWarehouse($skuCollect)
    {
        $shipToWarehouse = Product::whereIn('product.sku', $skuCollect)
        ->join('merchant_product_mapping', 'merchant_product_mapping.sku', '=', 'product.sku')
        ->join('merchant', 'merchant.id', '=', 'merchant_product_mapping.merchant_id')
        ->groupBy('merchant_id')
        ->orderBy(\DB::raw('FIELD(IF(product.default_ship_to_warehouse, product.default_ship_to_warehouse, merchant.default_ship_to_warehouse), "ES_HK", "ES_DGME", "4PXDG_PL")'))
        ->select(\DB::raw('IF(product.default_ship_to_warehouse, product.default_ship_to_warehouse, merchant.default_ship_to_warehouse) default_ship_to_warehouse'))
        ->first()
        ->default_ship_to_warehouse;

        return $shipToWarehouse;
    }

    public function getQuotationTypes($deliveryType)
    {
        return DeliveryTypeMapping::where('delivery_type', $deliveryType)
            ->where('merchant_type', 'ACCELERATOR')
            ->get()
            ->pluck('quotation_type')
            ->toArray();
    }

    public function shippingCouriers($defaultShipToWarehouse='ES_HK', $quotationTypes=[], $deliveryCountryId='')
    {
        return AcceleratorShipping::where('warehouse', '=', $defaultShipToWarehouse)
            ->whereIn('courier_type', $quotationTypes)
            ->where('country_id', '=', $deliveryCountryId)
            ->where('merchant_id', '=', 'ALL')
            ->where('status', 1)
            ->get()
            ->pluck('courier_id')
            ->toArray();
    }

}
