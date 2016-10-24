<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcceleratorShipping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_courier_master_shipping';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
