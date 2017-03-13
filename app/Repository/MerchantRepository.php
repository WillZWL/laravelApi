<?php

namespace App\Repository;

use App\Models\Merchant;
use App\Models\MerchantBalance;

class MerchantRepository
{
    public function all()
    {
        return Merchant::whereStatus(1)->get();
    }

    public function balance($request)
    {
        if ($request->get('merchant_id')) {
            return MerchantBalance::where('merchant_id', $request->get('merchant_id'))->get();
        } else {
            return MerchantBalance::get();
        }
    }
}
