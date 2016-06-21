<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSkuMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'marketplace_sku_mapping';

    public $timestamps = false;

    public $incrementing = false;

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'sku', 'sku');
    }

    public function fulfillmentCenter($fulfillment = null)
    {
        $relation = $this->hasMany('App\Models\FulfillmentCenter', 'mp_control_id', 'mp_control_id');
        if ($fulfillment) {
            $relation->where('fulfillment_method', '=', $fulfillment);
        }

        return $relation;
    }
}
