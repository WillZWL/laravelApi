<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonShippingAddress extends Model
{
    protected $fillable = [
        'amazon_order_id',
        'name',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'county',
        'district',
        'state_or_region',
        'postal_code',
        'country_code',
        'phone'
    ];

    public function amazonOrder()
    {
        $this->belongsTo('App\Models\AmazonOrder');
    }
}
