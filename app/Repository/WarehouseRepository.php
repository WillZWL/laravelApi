<?php

namespace App\Repository;

use App\Models\Warehouse;

class WarehouseRepository
{
    public function all()
    {
        return Warehouse::all();
    }

    public function defaultWarehouse()
    {
        return Warehouse::whereDefaultWarehouseStatus(1)->get();
    }
}