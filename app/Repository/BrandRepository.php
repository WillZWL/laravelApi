<?php

namespace App\Repository;

use App\Models\Brand;

class BrandRepository
{
    public function all()
    {
        return Brand::all();
    }
}
