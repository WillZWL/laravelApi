<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpFixedFee extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'mp_fixed_fee';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
