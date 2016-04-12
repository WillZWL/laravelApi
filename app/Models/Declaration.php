<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Declaration extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'declaration';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
