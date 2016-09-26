<?php

namespace App\Repository;

use App\Models\MarketStore;

class MarketStoreRepository
{
    public function all()
    {
        return MarketStore::all();
    }

    public function getStoresForMarketplace($marketplace)
    {
        return MarketStore::where('marketplace', $marketplace)->get();
    }
}
