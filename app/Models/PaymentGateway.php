<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'payment_gateway';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
