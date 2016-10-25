<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexBatch extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'flex_batch';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}
