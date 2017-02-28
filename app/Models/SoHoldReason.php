<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoHoldReason extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_hold_reason';

    public $primaryKey = 'so_no';

    public $timestamps = false;
}
