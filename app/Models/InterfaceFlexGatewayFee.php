<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceFlexGatewayFee extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'interface_flex_gateway_fee';

    public $primaryKey = 'trans_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}
