<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IwmsDeliveryOrderLog extends Model
{
    //
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function batchRequest()
    {
        return $this->belongsTo('App\Models\BatchRequest', 'batch_id');
    }

    public function iwmsLgsOrderStatusLog()
    {
        return $this->belongsTo('App\Models\IwmsLgsOrderStatusLog', "so_no", 'reference_no');
    }
}
