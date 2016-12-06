<?php

namespace App\Repository;

use App\Models\WeightCourier;

class WeightCourierRepository
{
    public function all()
    {
        return WeightCourier::all();
    }
}

