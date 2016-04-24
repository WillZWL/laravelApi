<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'sku_mapping';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['email'];
}
