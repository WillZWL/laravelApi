<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'config';

    public $primaryKey = 'variable';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}
