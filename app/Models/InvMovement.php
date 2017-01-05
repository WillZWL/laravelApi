<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvMovement extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'inv_movement';

    protected $primaryKey = 'trans_id';
    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}

