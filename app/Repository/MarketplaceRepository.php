<?php

namespace App\Repository;

use App\Models\Marketplace;

class MarketplaceRepository
{
    public function all($requestData = [])
    {

        if (isset($requestData['marketplace'])) {
            return Marketplace::where('id','like','%'. strtoupper($requestData['marketplace']))
                ->get();
        } else {
            return Marketplace::all();
        }
    }
}
