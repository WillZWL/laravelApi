<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class So extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so';

    public $timestamps = false;

    function soItem()
    {
        return $this->hasMany('App\Models\SoItem', 'so_no', 'so_no');
    }

    public function soItemDetail()
    {
        return $this->hasMany('App\Models\SoItemDetail', 'so_no', 'so_no');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'id', 'client_id');
    }
}
