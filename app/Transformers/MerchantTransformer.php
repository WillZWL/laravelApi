<?php

namespace App\Transformers;

use App\Models\Merchant;
use League\Fractal\TransformerAbstract;

class MerchantTransformer extends TransformerAbstract
{
    public function transform(Merchant $merchant)
    {
        return [
            'merchant_id' => $merchant->id,
            'merchant_short_id' => $merchant->short_id,
            'merchant_name' => $merchant->merchant_name,
        ];
    }
}
