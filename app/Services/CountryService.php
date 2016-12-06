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

    public function countryWithState()
    {
        $countries =  $this->countryRepository->countryWithState();

        $formattedCountryWithState = $countries->groupBy('id')->map(function ($countries) {
            $country =  $countries->first();
            return [
                'id' => $country->id,
                'name' => $country->name,
                'states' => $country->countryState->map(function ($state) {
                    return [
                        'id' => $state->state_id,
                        'name' => $state->name,
                    ];
                })->toArray(),
            ];
        })->values();

        return $formattedCountryWithState;
    }
}
