<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentCenter extends Model
{
    protected $guarded = [];

    public function marketplaceSkuMapping()
    {
        return $this->belongsTo('App\Models\MarketplaceSkuMapping', 'mp_control_id', 'mp_control_id');
    }
}
