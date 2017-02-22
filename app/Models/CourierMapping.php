<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierMapping extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'courier_mapping';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}
