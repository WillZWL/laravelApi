<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\PlatformMarketConstService;

class PlatformMarketOrder extends Model
{
    protected $table = 'platform_market_order';
    protected $primaryKey = 'id';
    protected $guarded = [];

    /***************************************************/
    /****              relations method             ****/
    /***************************************************/

    public function store()
    {
        return $this->belongsTo('App\Models\Store');
    }

    public function platformMarketShippingAddress()
    {
        return $this->hasOne('App\Models\PlatformMarketShippingAddress', 'id', 'shipping_address_id');
    }

    public function platformMarketOrderItem()
    {
        return $this->hasMany('App\Models\PlatformMarketOrderItem', 'platform_order_id', 'platform_order_id');
    }

    public function so()
    {
        return $this->belongsTo('App\Models\So');
    }

    public function marketplaceProduct()
    {

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
        $notInStatus = array(
            PlatformMarketConstService::ORDER_STATUS_CANCEL,
            PlatformMarketConstService::ORDER_STATUS_PENDING,
            PlatformMarketConstService::ORDER_STATUS_RETURENED,
            PlatformMarketConstService::ORDER_STATUS_UNCONFIRMED
        );

        $platformOrderIds = self::join('platform_market_order_item AS item', 'item.platform_order_id', '=', 'platform_market_order.platform_order_id')
            ->where('acknowledge', '=', '0')
            ->where('item.seller_sku', '=', '')
            ->whereNotIn('esg_order_status',$notInStatus)
            ->groupBy('item.platform_order_id')
            ->pluck('item.platform_order_id')
            ->toArray();

        if ($platformOrderIds) {
            return $query->where('acknowledge', '=', '0')
                ->whereNotIn('platform_order_id', $platformOrderIds)
                ->whereNotIn('esg_order_status',$notInStatus);
        }

        return $query->where('acknowledge', '=', '0')
            ->whereNotIn('esg_order_status',$notInStatus);
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
            ->where('esg_order_status', '=', PlatformMarketConstService::ORDER_STATUS_UNSHIPPED)
            ->where('biz_type', '=', 'Amazon')
            ->where('acknowledge', '=', '1');
    }

    public function scopeLazadaUnshippedOrder($query)
    {
        return $query->where('esg_order_status', '=', PlatformMarketConstService::ORDER_STATUS_UNSHIPPED)
            ->where('biz_type', '=', 'Lazada')
            ->where('acknowledge', '=', '1');
    }

    public function scopeUnshippedOrder($query)
    {
        return $query->where('esg_order_status', '=', PlatformMarketConstService::ORDER_STATUS_UNSHIPPED)
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
