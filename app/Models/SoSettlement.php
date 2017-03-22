<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoSettlement extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_settlement';

    public $primaryKey = 'so_no';

    public $timestamps = false;
}
