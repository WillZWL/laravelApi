<?php

namespace App\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

class Inventory implements Filter
{
    /**
     * Apply a given search value to the builder instance.
     *
     * @param Builder $builder
     * @param mixed $value
     * @return Builder $builder
     */
    public static function apply(Builder $builder, $value)
    {
        return $builder->whereHas('inventory', function ($q) use ($value) {
            return $q->where('inventory.inventory', '<', $value["inventory"])
                    ->where('inventory.warehouse_id', $value["warehouseId"]);
        });
    }
}
