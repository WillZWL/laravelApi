<?php

namespace App\Repository;

use App\Models\HscodeCategory;

class HscodeCategoryRepository
{
    public function all()
    {
        return HscodeCategory::all();
    }
}
