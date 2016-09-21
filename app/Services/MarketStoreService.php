<?php

namespace App\Services;

use App\Repository\MarketStoreRepository;

class MarketStoreService
{
    private $marketStoreRepository;

    public function __construct(MarketStoreRepository $marketStoreRepository)
    {
        $this->marketStoreRepository = $marketStoreRepository;
    }

    public function all()
    {
        return $this->marketStoreRepository->all();
    }

    public function getStoresForMarketplace($marketplace)
    {
        return $this->marketStoreRepository->getStoresForMarketplace($marketplace);
    }
}
