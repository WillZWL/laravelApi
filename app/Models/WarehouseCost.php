<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseCost extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'warehouse_cost';

    public $timestamps = false;

    protected $guarded = ['create_at'];

    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse');
    }
}
