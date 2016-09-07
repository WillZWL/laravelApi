<?php

namespace App\Services;

use App\Http\Requests\MarketplaceProductSearchRequest;
use App\Repository\MarketplaceProductRepository;

class MarketplaceProductService
{
    private $marketplaceProductRepository;

    public function __construct(MarketplaceProductRepository $marketplaceProductRepository)
    {
        $this->marketplaceProductRepository = $marketplaceProductRepository;
    }

    public function search(MarketplaceProductSearchRequest $searchRequest)
    {
    }
}
