<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AmazonOrderItem extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('quantity_ordered', function (Builder $builder) {
            $builder->where('quantity_ordered', '>', 0);
        });
    }

    public function amazonOrder()
    {
        return $this->belongsTo('App\Models\AmazonOrder');
    }

    public function marketplaceSkuMapping()
    {
        return $this->hasOne('App\Models\MarketplaceSkuMapping', 'marketplace_sku', 'seller_sku');
    }

}
