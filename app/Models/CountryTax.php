<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryTax extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'country_tax';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
