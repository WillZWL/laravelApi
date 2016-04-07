<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'quotation';

    public $timestamps = false;

    public $incrementing = false;

    public function courierInfo()
    {
        return $this->belongsTo('App\Models\CourierInfo', 'courier_id', 'courier_id');
    }
}
