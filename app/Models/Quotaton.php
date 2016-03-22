<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotaton extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'quotation';

    public $timestamps = false;

    public $incrementing = false;
}
