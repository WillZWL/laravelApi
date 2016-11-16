<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketExcludeSku extends Model
{
    //
    protected $table = 'platform_market_exclude_sku';
    protected $primaryKey = 'id';
    protected $guarded = ['create_at'];
}
