<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformOrderDeliveryScore extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'platform_order_delivery_score';

    public $timestamps = false;
}
