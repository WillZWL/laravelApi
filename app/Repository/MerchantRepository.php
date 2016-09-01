<?php

namespace App\Repository;

use App\Models\Merchant;

class MerchantRepository
{
    public function all()
    {
        return Merchant::all();
    }
}
