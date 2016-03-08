<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'client';

    public $timestamps = false;

    public function so()
    {
        return $this->hasMany('App\Models\So', 'client_id', 'id');
    }
}
