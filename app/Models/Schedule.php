<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'type',
        'uri',
        'status',
        'remark',
        'last_access_time'
    ];
}
