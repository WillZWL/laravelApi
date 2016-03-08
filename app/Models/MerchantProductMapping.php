<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantProductMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_product_mapping';

    public $timestamps = false;

    public function merchant()
    {
        return $this->belongsTo('App\Models\Merchant', 'id', 'merchant_id');
    }
}
