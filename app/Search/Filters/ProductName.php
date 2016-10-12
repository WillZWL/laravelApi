<?php

namespace App\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

class ProductName implements Filter
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
            return $q->where('product.name', 'like', "%{$value}%");
        });
    }
}
