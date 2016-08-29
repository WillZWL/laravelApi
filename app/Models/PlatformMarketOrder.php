<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\PlatformOrderService;

class PlatformMarketOrder extends Model
{
    protected $table = 'platform_market_order';
    protected $primaryKey = 'id';
    protected $guarded = [];

    /***************************************************/
    /****              relations method             ****/
    /***************************************************/

    public function platformMarketShippingAddress()
    {
        return $this->hasOne('App\Models\PlatformMarketShippingAddress', 'id', 'shipping_address_id');
    }

    public function platformMarketOrderItem()
    {
        return $this->hasMany('App\Models\PlatformMarketOrderItem', 'platform_order_id', 'platform_order_id');
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
            ->where('esg_order_status', '!=', PlatformOrderService::ORDER_STATUS_CANCEL)
            ->where('esg_order_status', '!=', PlatformOrderService::ORDER_STATUS_PENDING);
    }

    /**
     * Get all waiting for ship order.
     *
     * @param $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAmazonUnshippedOrder($query)
    {
        return $query->where('fulfillment_channel', '=', 'MFN')
            ->where('esg_order_status', '=', PlatformOrderService::ORDER_STATUS_UNSHIPPED)
            ->where('biz_type', '=', 'Amazon')
            ->where('acknowledge', '=', '1');
    }

    public function scopeLazadaUnshippedOrder($query)
    {
        return $query->where('esg_order_status', '=', PlatformOrderService::ORDER_STATUS_UNSHIPPED)
            ->where('biz_type', '=', 'Lazada')
            ->where('acknowledge', '=', '1');
    }

    public function scopeUnshippedOrder($query)
    {
        return $query->where('esg_order_status', '=', PlatformOrderService::ORDER_STATUS_UNSHIPPED)
            ->where('acknowledge', '=', '1');
    }

    public function scopeAmazonOrder($query)
    {
        return $query->where('biz_type', '=', 'Amazon')
            ->where('acknowledge', '=', '1');
    }

    public function scopeLazadaOrder($query)
    {
        return $query->where('biz_type', '=', 'Lazada')
            ->where('acknowledge', '=', '1');
    }
}
