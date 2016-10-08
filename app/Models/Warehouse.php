<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'warehouse';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function warehouseCost()
    {
        return $this->hasOne('App\Models\WarehouseCost');
    }
}
