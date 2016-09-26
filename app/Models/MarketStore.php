<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketStore extends Model
{
    protected $guarded = ['id'];

    public function platformMarketOrder()
    {
        return $this->hasMany('App\Models\PlatformMarketOrder');
    }

    public function userStore()
    {
        return $this->belongsToMany('App\Models\UserStore');
    }
}
