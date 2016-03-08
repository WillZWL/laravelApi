<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant';

    public $timestamps = false;

    public function merchantProductMapping()
    {
        return $this->hasMany('App\Models\MerchantProductMapping', 'merchant_id', 'id');
    }
}
