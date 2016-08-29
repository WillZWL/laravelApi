<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightCourier extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'weight_courier';

    public $timestamps = false;

    public $incrementing = false;

    public static function getWeightId($weight)
    {
        return self::where('weight', '>=', $weight)->first()->id;
    }
}
