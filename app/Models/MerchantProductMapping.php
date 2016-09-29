<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantProductMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_product_mapping';

    protected $primaryKey = 'sku';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function merchant()
    {
        return $this->belongsTo('App\Models\Merchant', 'merchant_id', 'id');
    }

    public function product()
    {
        return $this->hasOne('App\Models\Product', 'sku', 'sku');
    }

    public function marketplaceSkuMapping()
    {
        return $this->hasMany('App\Models\MarketplaceSkuMapping', 'sku', 'sku');
    }
}
