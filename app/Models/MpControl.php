<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpControl extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'mp_control';

    public $primaryKey = 'control_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
