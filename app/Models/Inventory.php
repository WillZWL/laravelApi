<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'inventory';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function marketplaceSkuMapping()
    {
        return $this->belongsToMany('App\Models\MarketplaceSkuMapping', 'prod_sku', 'sku');
    }
}
