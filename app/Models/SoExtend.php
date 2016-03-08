<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoExtend extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_extend';

    public $primaryKey = 'so_no';

    public $timestamps = false;
}
