<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class So extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so';

    protected $primaryKey = 'so_no';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function hasInternalBattery()
    {
        $result = $this->soItem->contains(function ($key, $value) {
            return $value->product->battery == 1;
        });

        return $result;
    }

    public function hasExternalBattery()
    {
        $result = $this->soItem->contains(function ($key, $value) {
            return $value->product->battery == 2;
        });

        return $result;
    }

    public function soItem()
    {
        return $this->hasMany('App\Models\SoItem', 'so_no', 'so_no');
    }

    public function soItemDetail()
    {
        return $this->hasMany('App\Models\SoItemDetail', 'so_no', 'so_no');
    }

    public function salesOrderStatistic()
    {
        return $this->hasMany('App\Models\SalesOrderStatistic', 'so_no', 'so_no');
    }

    public function soAllocate()
    {
        return $this->hasMany('App\Models\SoAllocate', 'so_no', 'so_no');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'client_id', 'id');
    }

    public function courierInfo()
    {
        return $this->belongsTo('App\Models\CourierInfo', 'esg_quotation_courier_id', 'courier_id');
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country', 'delivery_country_id', 'id');
    }

    public function sellingPlatform()
    {
        return $this->belongsTo('App\Models\SellingPlatform', 'platform_id');
    }

    public function flexSoFee()
    {
        return $this->hasMany('App\Models\FlexSoFee', 'so_no', 'so_no');
    }

    public function platformMarketOrder()
    {
        return $this->hasOne('App\Models\PlatformMarketOrder', 'so_no', 'so_no');
    }

    public function amazonOrder()
    {
        return $this->hasOne('App\Models\AmazonOrder', 'amazon_order_id', 'platform_order_id');
    }

    public function soPriorityScore()
    {
        return $this->hasOne('App\Models\SoPriorityScore', 'so_no', 'so_no');
    }

    public function scopeUnshippedOrder($query)
    {
        return $query->where('so.status', 5)
                ->where('so.hold_status', 0)
                ->where('so.refund_status', 0)
                ->where('so.prepay_hold_status', 0);
    }

    public function scopeShippedOrder($query)
    {
        return $query->where('so.status', 6)
                ->where('so.hold_status', 0)
                ->where('so.refund_status', 0)
                ->where('so.prepay_hold_status', 0);
    }


    public function scopeAllocateOrders($query, $soNoCollection = [])
    {
        return $query->whereIn('so_no', $soNoCollection)
            ->with('SoItemDetail')
            ->wherePlatformGroupOrder('1')
            ->where('status', '>', 2)
            ->where('status', '<', 5)
            ->whereHoldStatus('0')
            ->whereRefundStatus('0')
            ->whereMerchantHoldStatus('0')
            ->wherePrepayHoldStatus('0')
            ->whereIsTest('0')
            ->get();
    }

    public function scopePaidOrder($query)
    {
        return $query->where('so.status', 3)
                ->where('so.hold_status', 0)
                ->where('so.refund_status', 0)
                ->where('so.prepay_hold_status', 0)
                ->where('so.merchant_hold_status', 0)
                ->where('is_test', 0);
    }

}
