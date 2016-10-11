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
        $value = array_filter($value, function ($subValue) {
            return $subValue !== '';
        });

        if (empty($value)) {
            return $builder;
        }

        return $builder->whereHas('inventory', function ($q) use ($value) {
            foreach ($value as $k => $v) {
                switch ($k) {
                    case 'warehouse_id':
                        $q = $q->where('inventory.warehouse_id', $v);
                        break;
                    case 'from_inventory':
                        $q = $q->where('inventory.inventory', '>=', $v);
                        break;
                    case 'end_inventory':
                        $q = $q->where('inventory.inventory', '<=', $v);
                        break;
                }
            }

            return $q;
        });
    }
}
