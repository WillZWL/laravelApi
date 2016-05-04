<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonShippingAddress extends Model
{
    protected $guarded = [];

    public function amazonOrder()
    {
        $this->belongsTo('App\Models\AmazonOrder');
    }
}
