<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightCourier extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'weight_courier';

    public $timestamps = false;

    public $incrementing = false;

    static public function getWeightId($weight)
    {
        return WeightCourier::where('weight', '>=', $weight)->min('id');
    }
}
