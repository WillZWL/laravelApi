<?php

namespace App\Transformers;

use App\Models\Warehouse;
use League\Fractal\TransformerAbstract;

class WarehouseTransformer extends TransformerAbstract
{
    public function transform(Warehouse $warehouse)
    {
        return [
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
        ];
    }
}
