<?php

namespace App\Repository;

use App\Models\Supplier;

class SupplierRepository
{
    public function all()
    {
        return Supplier::all();
    }
}