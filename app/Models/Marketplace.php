<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marketplace extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'marketplace';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
