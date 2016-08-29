<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketOrderItem extends Model
{
    protected $table = 'platform_market_order_item';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function platformMarketOrder()
    {
        return $this->belongsTo('App\Models\PlatformMarketOrder');
    }
}
