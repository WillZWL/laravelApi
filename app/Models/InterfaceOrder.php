<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceOrder extends Model
{
    protected $fillable = [
        'batch_id',
        'platform_order_id',
        'biz_type',
        'amount',
        'currency',
        'order_create_date',
        'status',
        'bill_name',
        'bill_address_line_1',
        'bill_address_line_2',
        'bill_address_line_3',
        'bill_postal_code',
        'bill_city',
        'bill_region_or_state',
        'bill_country_id',
        'delivery_name',
        'delivery_address_line_1',
        'delivery_address_line_2',
        'delivery_address_line_3',
        'delivery_postal_code',
        'delivery_city',
        'delivery_region_or_state',
        'delivery_country_id',
        'cost',
        'weight',
        'batch_status',
        'delivery_charge',
        'courier_id'
    ];
}
