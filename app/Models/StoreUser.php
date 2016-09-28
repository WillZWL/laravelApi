<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreUser extends Model
{
    public function platformMarketOrder()
    {
        return $this->hasMany('App\Models\PlatformMarketOrder');
    }
}
