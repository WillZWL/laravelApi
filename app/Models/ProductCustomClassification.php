<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductCustomClassification extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'product_custom_classification';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'sku');
    }

}