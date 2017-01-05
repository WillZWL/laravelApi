<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoAllocate extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'so_allocate';

    public $timestamps = false;

    protected $guarded = ['create_at'];

    public function so()
    {
        return $this->belongsTo('App\Models\So', 'so_no', 'so_no');
    }

    public function soShipment()
    {
        return $this->belongsTo('App\Models\SoShipment', 'sh_no', 'sh_no');
    }

    public function InvMovement()
    {
        return $this->hasOne('App\Models\InvMovement', 'id', 'ship_ref')
                ->where("status", 1);
    }
}
