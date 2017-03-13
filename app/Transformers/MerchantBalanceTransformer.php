<?php

namespace App\Transformers;

use App\Models\MerchantBalance;
use League\Fractal\TransformerAbstract;

class MerchantBalanceTransformer extends TransformerAbstract
{
    public function transform(MerchantBalance $merchantBalance)
    {
        return [
            'merchant_id' => $merchantBalance->merchant_id,
            'currency_id' => $merchantBalance->currency_id,
            'balance' => number_format($merchantBalance->balance, 2, '.', '')
        ];
    }
}
