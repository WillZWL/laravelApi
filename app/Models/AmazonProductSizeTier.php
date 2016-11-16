<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonProductSizeTier extends Model
{
    public function marketplaceSkuMapping()
    {
        return $this->belongsTo('App\Models\MarketplaceSkuMapping');
    }
}
