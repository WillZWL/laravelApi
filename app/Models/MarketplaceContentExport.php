<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceContentExport extends Model
{

    protected $table = 'marketplace_content_export';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    public function marketplaceContentField()
    {
        return $this->belongsTo('App\Models\MarketplaceContentField', 'field_value', 'value');
    }
}
