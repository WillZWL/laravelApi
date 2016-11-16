<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonFbaFee extends Model
{
    public function marketplaceSkuMapping()
    {
        return $this->belongsTo('App\Models\MarketplaceSkuMapping');
    }
}
