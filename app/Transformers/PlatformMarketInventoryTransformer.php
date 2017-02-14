<?php

namespace App\Transformers;

use App\Models\PlatformMarketInventory;
use League\Fractal\TransformerAbstract;

class PlatformMarketInventoryTransformer extends TransformerAbstract
{
    public function transform(PlatformMarketInventory $inventory)
    {
        return [
            'id' => $inventory->id,
            'warehouse_id' => $inventory->warehouse_id,
            'mattel_sku' => $inventory->mattel_sku,
            'dc_sku' =>$inventory->dc_sku,
            'inventory' => $inventory->inventory,
            'threshold' => $inventory->threshold,
        ];
    }
}
