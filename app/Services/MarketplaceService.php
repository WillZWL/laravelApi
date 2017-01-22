<?php

namespace App\Services;

use App\Repository\MarketplaceRepository;

class MarketplaceService
{
    private $marketplaceRepository;

    public function __construct(MarketplaceRepository $marketplaceRepository)
    {
        $this->marketplaceRepository = $marketplaceRepository;
    }

    public function getAllMarketplace($requestData = [])
    {
        return $this->marketplaceRepository->all($requestData);
    }
}
