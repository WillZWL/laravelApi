<?php

namespace App\Repository;

use App\Models\Country;

class CountryRepository
{
    public function all()
    {
        return Country::all();
    }

    public function countryWithState()
    {
        return Country::activeCountry()->with('countryState')->get();
    }
}
