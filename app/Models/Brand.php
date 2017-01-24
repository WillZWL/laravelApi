<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'brand';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function product()
    {
        return $this->hasMany('App\Models\Product', 'brand_id', 'id');
    }
}
