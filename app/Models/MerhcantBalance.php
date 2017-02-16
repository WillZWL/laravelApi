<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MerchantBalande extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_balance';

    public $timestamps = false;

    protected $guarded = ['create_at'];

    public function merchant()
    {
        return $this->belongsTo('App\Models\Merchant', 'id', 'merchant_id');
    }
}
