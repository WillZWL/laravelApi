<?php

namespace App\Transformers;

use App\Models\MarketplaceSkuMapping;
use League\Fractal\TransformerAbstract;

class MarketplaceProductTransformer extends TransformerAbstract
{
    public function transform(MarketplaceSkuMapping $product)
    {
        return [
            'id' => $product->id,
            'marketplace_id' => $product->marketplace_id,
            'country_id' => $product->country_id,
            'master_sku' => $product->skuMapping->ext_sku,
            'marketplace_sku' => $product->marketplace_sku,
            'marketplace_sku' => $product->marketplace_sku,
            'esg_sku' => $product->sku,
            'product_name' => $product->product->prod_grp_cd_name,
            'sourcing_status' => $product->supplierProduct->supplier_status,
            'selling_price' => $product->price,
            'profit' => $product->profit,
            'margin' => $product->margin,
            'warehouse' => $product->inventory()->get(['warehouse_id', 'inventory'])->keyBy('warehouse_id')->toArray(),
            'selected_delivery_type' => $product->delivery_type,
            'available_delivery_type' => ['STD', 'EXP', 'EXPED'],
            'listing_status' => $product->listing_status,
            'listing_quantity' => $product->inventory,
            'updated_at' => $product->modify_on,
            'updated_by' => $product->modify_by,
        ];
    }
}
