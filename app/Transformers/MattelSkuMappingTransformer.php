<?php

namespace App\Transformers;

use App\Models\MattelSkuMapping;
use League\Fractal\TransformerAbstract;

class MattelSkuMappingTransformer extends TransformerAbstract
{
    public function transform(MattelSkuMapping $mapping)
    {
        return [
            'id' => $mapping->id,
            'warehouse_id' => $mapping->warehouse_id,
            'mattel_sku' => $mapping->mattel_sku,
            'dc_sku' =>$mapping->dc_sku,
        ];
    }
}
