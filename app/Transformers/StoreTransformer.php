<?php

namespace App\Transformers;

use App\Models\Store;
use League\Fractal\TransformerAbstract;

class StoreTransformer extends TransformerAbstract
{
    public function transform(Store $store)
    {
        return [
            'id' => $store->id,
            'store_name' => $store->store_name,
            'store_code' => $store->store_code,
            'marketplace' => $store->marketplace,
            'country' => $store->country,
            'currency' => $store->currency,
        ];
    }
}