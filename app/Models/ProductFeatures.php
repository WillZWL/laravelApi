<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFeatures extends Model
{
    //
    protected $table = 'product_features';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function products()
    {
        return $this->belongsTo('App\Models\Product', 'esg_sku', 'sku');
    }
}
