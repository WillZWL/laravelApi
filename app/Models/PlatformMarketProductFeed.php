<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketProductFeed extends Model
{
    protected $table = 'platform_market_product_feeds';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function scopeProductFeed($query,$platform,$feedProcessingStatus)
    {
        return $productFeed = $query->where('feed_processing_status',$feedProcessingStatus)
            ->where('platform', $platform)
            ->get(); 
    }
}
