<?php

namespace App\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

class EndSurplus implements Filter
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
        return $builder->whereHas('product', function ($q) use ($value) {
            return $q->where('product.surplus_quantity', '<=', $value);
        });
    }
}
