<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProduct extends Model
{
    protected $fillable = [
        'marketplace_sku',
        'esg_sku',
        'marketplace_id',
        'country_id',
        'ean',
        'upc',
        'asin',
        'mp_category_id',
        'mp_sub_category_id',
        'price',
        'delivery_type',
        'listing_status',
        'process_status',
    ];

    public function products()
    {
        return $this->belongsTo('App\Models\Product', 'esg_sku', 'sku');
    }
}
