<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceClient extends Model
{
    protected $fillable = [
        'batch_id',
        'client_id',
        'password',
        'buyer_name',
        'buyer_email',
        'ship_name',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'country',
        'district',
        'state_or_region',
        'pastal_code',
        'country_code',
        'phone',
    ];
}
