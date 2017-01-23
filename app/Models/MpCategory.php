<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpCategory extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'mp_category';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function marketplaceSkuMapping()
    {
        return $this->belongsTo('App\Models\MarketplaceSkuMapping');
    }
}
