<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpListingFee extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'mp_listing_fee';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
