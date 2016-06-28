<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpIncoterm extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'sp_incoterm';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
