<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketAuthorization extends Model
{
    protected $table = 'platform_market_authorization';
    protected $primaryKey = 'id';
    protected $guarded = [];

    /**
     * Get not acknowledge order.
     *
     * @param $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMarketPlaceToken($query,$storeName)
    {
        $marketplaceId = strtoupper(substr($storeName, 0, -2));
        $countryCode = strtoupper(substr($storeName, -2));
        return $query->where('marketplace_id', '!=', $marketplaceId)
            ->where('country_id', '=', $countryCode);
    }

}
