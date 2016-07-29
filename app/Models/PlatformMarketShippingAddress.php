<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketShippingAddress extends Model
{
    protected $table='platform_market_shipping_address';
    protected $primaryKey="id";
    protected $guarded = [];

    public function PlatformMarketOrder()
    {
        $this->belongsTo('App\Models\PlatformMarketOrder');
    }
}
