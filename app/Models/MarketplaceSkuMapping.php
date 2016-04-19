<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSkuMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'marketplace_sku_mapping';

    public $timestamps = false;

    public $incrementing = false;

    public function Product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'sku');
    }
}