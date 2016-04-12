<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantClientType extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'merchant_client_type';

    public $timestamps = false;
}
