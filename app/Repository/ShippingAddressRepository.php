<?php

namespace App\Repository;

use App\Models\Country;

class ShippingAddressRepository
{
    private $countryId;

    private $stateId = '';

    private $countryName;

    private $stateName;

    public function __construct($countryId = '', $stateId = '')
    {
        $destination = Country::find($countryId);

        $this->countryId = $destination->id;
        $this->countryName = $destination->name;
    }
}
