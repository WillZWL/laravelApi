<?php

namespace App\Repository;

use App\Models\Store;

class StoreRepository
{
    public function all()
    {
        return Store::whereStatus(1)->get();
    }
}