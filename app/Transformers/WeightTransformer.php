<?php

namespace App\Transformers;

use App\Models\WeightCourier;
use League\Fractal\TransformerAbstract;

class WeightTransformer extends TransformerAbstract
{
    public function transform(WeightCourier $weight)
    {
        return [
            'weight' => $weight->weight,
        ];
    }
}
