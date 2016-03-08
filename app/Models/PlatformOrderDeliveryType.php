<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformOrderDeliveryType extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'platform_order_delivery_type';

    public $timestamps = false;
}
