<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'credentials',
    ];

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    public function platformMarketOrder()
    {
        return $this->hasMany('App\Models\PlatformMarketOrder');
    }
}
