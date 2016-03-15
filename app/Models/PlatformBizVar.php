<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBizVar extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'platform_biz_var';

    public $primaryKey = 'selling_platform_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
