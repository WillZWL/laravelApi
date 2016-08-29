<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmazonOrder extends Model
{
    protected $guarded = [];

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
     *
     * @param $query
     *
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
     *
     * @param $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnshippedOrder($query)
    {
        return $query->where('fulfillment_channel', '=', 'MFN')
            ->where('order_status', '=', 'Unshipped')
            ->where('acknowledge', '=', '1');
    }
}
