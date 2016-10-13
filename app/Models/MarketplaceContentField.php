<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceContentField extends Model
{

    protected $table = 'marketplace_content_field';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}
