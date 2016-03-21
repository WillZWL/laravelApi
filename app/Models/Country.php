<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'country';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    public function countryState()
    {
        return $this->hasMany('App\Models\CountryState', 'id', 'country_id');
    }
}
