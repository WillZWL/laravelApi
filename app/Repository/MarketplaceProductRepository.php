<?php

namespace App\Repository;

use App\Models\MarketplaceSkuMapping;

class MarketplaceProductRepository
{
    const PRODUCT_UPDATED = 1;
    const PRICE_UPDATED = 2;
    const INVENTORY_UPDATED = 4;
    const PRODUCT_DISCONTINUED = 64;

    public function search()
    {
        return MarketplaceSkuMapping::all();
    }

    public function update($product)
    {
        $p = MarketplaceSkuMapping::findOrFail($product['id']);

        if ($p->price != $product['price']) {
            $p->price = $product['price'];
            $p->process_status = $p->process_status | self::PRICE_UPDATED;
        }

        if ($p->delivery_type != $product['delivery_type']) {
            $p->delivery_type = $product['delivery_type'];
            $p->fulfillment = ($p->delivery_type == 'FBA') ? 'AFN' : 'MFN';

            $p->process_status = $p->process_status | self::INVENTORY_UPDATED;
        }

        if ($p->listing_status != $product['listing_status']) {
            $p->listing_status = $product['listing_status'];
        }

        if (isset($product['listing_quantity']) && ($p->inventory != $product['listing_quantity'])) {
            $p->inventory = $product['listing_quantity'];
            $p->process_status = $p->process_status | self::INVENTORY_UPDATED;
        }

        $p->save();
    }

    public function find($id)
    {
        return MarketplaceSkuMapping::findOrFail($id);
    }

    public function getMarketplaceProducts($requestData)
    {
        $query = MarketplaceSkuMapping::whereMarketplaceId($requestData['marketplace_id'])
            ->join('product AS p', 'p.sku', '=', 'marketplace_sku_mapping.sku')
            ->where('marketplace_sku_mapping.country_id', $requestData['country_id'])
            ->where('marketplace_sku_mapping.status', 1);
        if ($requestData['marketplace_skus']) {
            $query->whereIn('marketplace_sku_mapping.marketplace_sku', $requestData['marketplace_skus']);
        }
        if ($requestData['skus']) {
            $query->whereIn('p.sku', $requestData['skus']);
        }
        if ($requestData['colour_id']) {
            $query->where('p.colour_id', $requestData['colour_id']);
        }
        if ($requestData['version_id']) {
            $query->where('p.version_id', $requestData['version_id']);
        }
        if ($requestData['brand_id']) {
            $query->where('p.brand_id', $requestData['brand_id']);
        }
        if ($requestData['cat_id']) {
            $query->where('p.cat_id', $requestData['cat_id']);
        }
        if ($requestData['sub_cat_id']) {
            $query->where('p.sub_cat_id', $requestData['sub_cat_id']);
        }
        if ($requestData['sub_sub_cat_id']) {
            $query->where('p.sub_sub_cat_id', $requestData['sub_sub_cat_id']);
        }
        if ($requestData['hscode_cat_id']) {
            $query->where('p.hscode_cat_id', $requestData['hscode_cat_id']);
        }
        return $query->groupBy('marketplace_sku', 'marketplace_sku_mapping.sku')
            ->select('marketplace_sku_mapping.*')
            ->get();
    }
}
