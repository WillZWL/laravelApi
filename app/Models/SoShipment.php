<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoShipment extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_shipment';

    public $primaryKey = 'sh_no';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function courierInfo()
    {
        return $this->belongsTo('App\Models\CourierInfo', 'courier_id', 'courier_id');
    }
}
