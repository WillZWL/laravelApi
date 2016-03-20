<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellingPlatform extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'selling_platform';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function so()
    {
        return $this->hasMany('App\Models\So', 'id', 'platform_id');
    }
}
