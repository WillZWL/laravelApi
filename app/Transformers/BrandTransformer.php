<?php

namespace App\Transformers;

use App\Models\Brand;
use League\Fractal\TransformerAbstract;

class BrandTransformer extends TransformerAbstract
{
    public function transform(Brand $brand)
    {
        return [
            'brand_id' => $brand->id,
            'brand_name' => $brand->brand_name,
            'brand_manager' => $brand->brand_manager ?: '',
            'business_unit' => $brand->business_unit ?: '1',
        ];
    }
}
