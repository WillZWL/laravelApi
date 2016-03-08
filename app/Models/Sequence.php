<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'sequence';

    public $primaryKey = 'seq_name';

    public $timestamps = false;
}
