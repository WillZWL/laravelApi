<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductContent extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product_content';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'prod_sku');
    }

}