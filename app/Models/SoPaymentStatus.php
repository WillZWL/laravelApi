<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoPaymentStatus extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_payment_status';

    public $primaryKey = 'so_no';

    public $timestamps = false;

    public function paymentGateway()
    {
        return $this->hasOne('App\Models\PaymentGateway', 'id', 'payment_gateway_id');
    }
}
