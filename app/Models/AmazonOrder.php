<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;

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

    /***************************************************/
    /****              relations method             ****/
    /***************************************************/

    public function amazonShippingAddress()
    {
        return $this->hasOne('App\Models\AmazonShippingAddress', 'id', 'shipping_address_id');
    }

    public function amazonOrderItem()
    {
        return $this->hasMany('App\Models\AmazonOrderItem', 'amazon_order_id', 'amazon_order_id');
    }

    /***************************************************/
    /****                scope method               ****/
    /***************************************************/

    /**
     * Get not acknowledge order.
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReadyOrder($query)
    {
        return $query->where('acknowledge', '=', '0')
                        ->where('order_status', '!=', 'Canceled')
                        ->where('order_status', '!=', 'Pending');
    }

    /**
     * Get all waiting for ship order.
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnshippedOrder($query)
    {
        return $query->where('fulfillment_channel', '=', 'MFN')
            ->where('order_status', '=', 'Unshipped')
            ->where('acknowledge', '=', '1');
    }
}
