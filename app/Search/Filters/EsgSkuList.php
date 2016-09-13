<?php

namespace App\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

class EsgSkuList implements Filter
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
        $skuList = array_map('trim', explode("\r\n", $value));

        return $builder->whereIn('marketplace_sku_mapping.sku', $skuList);
    }
}
