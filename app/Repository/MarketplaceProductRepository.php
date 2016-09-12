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

        $p->save();
    }

    public function find($id)
    {
        return MarketplaceSkuMapping::findOrFail($id);
    }
}
