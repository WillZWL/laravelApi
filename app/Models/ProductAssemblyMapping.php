<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAssemblyMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product_assembly_mapping';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'sku');
    }

    public function merchantProductMapping($merchantId)
    {
        return $this->hasOne('App\Models\MerchantProductMapping', 'sku', 'sku')
                    ->where('merchant_id', $merchantId);
    }

    public function scopeActive($query)
    {
        return $query->where('product_assembly_mapping.status', '=', 1);
    }
}
