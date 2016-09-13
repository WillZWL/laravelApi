<?php

namespace App\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

class ListingStatus implements Filter
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
        return $builder->where('marketplace_sku_mapping.listing_status', $value);
    }
}
