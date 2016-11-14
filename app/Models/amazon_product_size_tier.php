<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class amazon_product_size_tier extends Model
{
    public function marketplaceSkuMapping()
    {
        return $this->belongsTo('App\Models\MarketplaceSkuMapping');
    }
}
