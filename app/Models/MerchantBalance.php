<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantBalance extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_balance';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['create_at'];

}
