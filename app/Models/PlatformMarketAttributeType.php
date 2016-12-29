<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketAttributeType extends Model
{
    //
    protected $table = 'platform_market_attribute_type';
    protected $primaryKey = 'id';
    protected $guarded = ['create_at'];

    public function platformMarketAttributeOptions()
    {
        return $this->hasMany('App\Models\PlatformMarketAttributeOptions', 'attribute_type_id', 'id');
    }
}
