<?php

namespace App\Repository;

use App\Models\Marketplace;

class MarketplaceRepository
{
    public function all()
    {
        return Marketplace::all();
    }
}
