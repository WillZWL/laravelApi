<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryState extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'country_state';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

    /**
     * Try to find record by $stateNameOrId, if can't find, try to base on name,.
     *
     * @param $countryCode
     * @param $stateNameOrId
     *
     * @return string $state_id or ''
     */
    public static function getStateId($countryCode, $stateNameOrId)
    {
        if (empty($stateNameOrId)) {
            return '';
        }

        $countryState = self::where('country_id', '=', $countryCode)
            ->where('state_id', '=', $stateNameOrId)
            ->orWhere('name', '=', $stateNameOrId)
            ->first();
        if ($countryState) {
            return $countryState->state_id;
        }

        return '';
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    public function courierCost()
    {
        return $this->hasMany('App\Models\CourierCost');
    }
}
