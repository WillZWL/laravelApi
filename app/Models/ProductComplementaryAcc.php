<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductComplementaryAcc extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product_complementary_acc';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'accessory_sku', 'sku');
    }

    public function scopeActive($query)
    {
        return $query->where('product_complementary_acc.status', '=', 1);
    }
}
