<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexGatewayFee extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'flex_gateway_fee';

    // public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}
