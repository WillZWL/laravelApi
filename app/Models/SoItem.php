<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoItem extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_item';

    public $timestamps = false;

    protected $guarded = ['create_at'];

    public function so()
    {
        return $this->belongsTo('App\Models\So', 'so_no', 'so_no');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'prod_sku', 'sku');
    }

    public function hscodeCategory()
    {
        return $this->belongsTo('App\Models\HscodeCategory', 'hscode_cat_id', 'id');
    }
}
