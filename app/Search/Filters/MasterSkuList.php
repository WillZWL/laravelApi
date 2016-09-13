<?php

namespace App\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

class MasterSkuList implements Filter
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
        $masterSkuList = array_map('trim', explode("\r\n", $value));

        return $builder->whereHas('skuMapping', function ($q) use ($masterSkuList) {
            return $q->whereIn('sku_mapping.ext_sku', $masterSkuList);
        });
    }
}
