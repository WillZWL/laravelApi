<?php

namespace App\Transformers;

use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;

class ShippingTypeWithProfitTransformer extends TransformerAbstract
{
    public function transform(Collection $shippingTypeWithProfit)
    {
        return [

        ]   ;
        //return $shippingTypeWithProfit->toArray();
    }
}
