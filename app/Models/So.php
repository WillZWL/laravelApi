<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class So extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so';

    protected $primaryKey = 'so_no';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function hasInternalBattery()
    {
        $result = $this->soItem->contains(function ($key, $value) {
            return $value->product->battery == 1;
        });

        return $result;
    }

    public function hasExternalBattery()
    {
        $result = $this->soItem->contains(function ($key, $value) {
            return $value->product->battery == 2;
        });

        return $result;
    }

    function soItem()
    {
        return $this->hasMany('App\Models\SoItem', 'so_no', 'so_no');
    }

    public function soItemDetail()
    {
        return $this->hasMany('App\Models\SoItemDetail', 'so_no', 'so_no');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'id', 'client_id');
    }

    public function courierInfo()
    {
        return $this->belongsTo('App\Models\CourierInfo', 'esg_quotation_courier_id', 'courier_id');
    }
}
