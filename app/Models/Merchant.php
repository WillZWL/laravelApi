<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['create_at'];

    public function merchantProductMapping()
    {
        return $this->hasMany('App\Models\MerchantProductMapping', 'merchant_id', 'id');
    }

    public function merchantQuotation()
    {
        return $this->hasMany('App\Models\MerchantQuotation', 'merchant_id', 'id');
    }
}
