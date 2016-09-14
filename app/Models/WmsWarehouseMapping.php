<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmsWarehouseMapping extends Model
{
    //
    public $connection = 'mysql_esg';

    protected $table = 'wms_warehouse_mapping';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $incrementing = false;

    protected $guarded = ['create_at'];

}
