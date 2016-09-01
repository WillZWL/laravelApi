<?php

namespace App\Repository;

use App\Models\Country;

class CountryRepository
{
    public function all()
    {
        return Country::all();
    }
}
