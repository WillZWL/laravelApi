<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierInfo extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'courier_info';

    public $primaryKey = 'courier_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function so()
    {
        return $this->hasMany('App\Models\So', 'courier_id', 'recommend_courier_id');
    }

    public function shipment()
    {
        return $this->hasMany('App\Models\SoShipment', 'courier_id', 'courier_id');
    }

    public function quotation()
    {
        return $this->hasMany('App\Models\Quotation', 'courier_id', 'courier_id');
    }

    public function deliveryTypeMapping()
    {
        return $this->hasOne('App\Models\DeliveryTypeMapping', 'courier_type', 'type');
    }

    public function courierCost()
    {
        return $this->hasMany('App\Models\CourierCost', 'courier_id');
    }
}
