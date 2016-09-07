<?php

namespace App\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

class MerchantId implements Filter
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
        return $builder->whereHas('merchantProductMapping', function ($q) use ($value) {
            return $q->where('merchant_product_mapping.merchant_id', $value);
        });
    }
}
