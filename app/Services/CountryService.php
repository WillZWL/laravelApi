<?php

namespace App\Services;

use App\Repository\CountryRepository;

class CountryService
{
    private $countryRepository;

    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function all()
    {
        return $this->countryRepository->all();
    }
}
