<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchRequest extends Model
{
    //
    protected $guarded = [];

    public function iwmsDeliveryOrderLog()
    {
        return $this->hasMany('App\Models\IwmsDeliveryOrderLog', 'batch_id', 'id');
    }
}
