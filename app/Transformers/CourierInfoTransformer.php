<?php

namespace App\Transformers;

use App\Models\CourierInfo;
use League\Fractal\TransformerAbstract;

class CourierInfoTransformer extends TransformerAbstract
{
    public function transform(CourierInfo $courierInfo)
    {
        return [
            'courier_id' => $courierInfo->courier_id,
            'courier_name' => $courierInfo->courier_name,
        ];
    }
}
