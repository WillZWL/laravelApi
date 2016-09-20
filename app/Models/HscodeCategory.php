<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HscodeCategory extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'hscode_category';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
