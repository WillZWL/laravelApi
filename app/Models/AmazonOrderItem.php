<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonOrderItem extends Model
{
    protected $guarded = [];

    public function amazonOrder()
    {
        return $this->belongsTo('App\Models\AmazonOrder');
    }
}
