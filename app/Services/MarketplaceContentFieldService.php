<?php

namespace App\Services;

use App\Repository\MarketplaceContentFieldRepository;

class MarketplaceContentFieldService
{
    private $marketplaceContentFieldRepository;

    public function __construct(MarketplaceContentFieldRepository $marketplaceContentFieldRepository)
    {
        $this->marketplaceContentFieldRepository = $marketplaceContentFieldRepository;
    }

    public function all()
    {
        return $this->marketplaceContentFieldRepository->all();
    }
}
