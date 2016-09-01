<?php

namespace App\Transformers;

use App\Models\Country;
use League\Fractal\TransformerAbstract;

class CountryTransformer extends TransformerAbstract
{
    public function transform(Country $country)
    {
        return [
            'country_id' => $country->id,
            'country_name' => $country->name,
        ];
    }
}
