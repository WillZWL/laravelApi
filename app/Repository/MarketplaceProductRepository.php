<?php

namespace App\Repository;

use App\Models\MarketplaceSkuMapping;

class MarketplaceProductRepository
{
    public function search()
    {
        return MarketplaceSkuMapping::all();
    }
}
