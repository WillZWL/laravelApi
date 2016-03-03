<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonOrder extends Model
{
    protected $fillable = [
        'platform',
        'amazon_order_id',
        'purchase_date',
        'last_update_date',
        'order_status',
        'fulfillment_channel',
        'sales_channel',
        'ship_service_level',
        'shipping_address_id',
        'total_amount',
        'currency',
        'number_of_items_shipped',
        'number_of_items_unshippped',
        'payment_method',
        'buyer_name',
        'buyer_email',
        'earliest_ship_date',
        'latest_ship_date',
        'earliest_delivery_date',
        'latest_delivery_date'
    ];

    public function amazonShippingAddress()
    {
        $this->hasOne('App\Models\AmazonShippingAddress');
    }

    public function amazonOrderItem()
    {
        $this->hasMany('App\Models\AmazonOrderItem');
    }
}
