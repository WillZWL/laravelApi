<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product';

    protected $primaryKey = 'sku';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function productComplementaryAcc()
    {
        return $this->hasMany('App\Models\ProductComplementaryAcc', 'sku', 'accessory_sku');
    }
}
