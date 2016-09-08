<?php

namespace App\Services;

use App\Http\Requests\MarketplaceProductSearchRequest;
use App\Http\Requests\ProfitEstimateRequest;
use App\Repository\MarketplaceProductRepository;
use App\Search\MarketplaceProductSearch;

class MarketplaceProductService
{
    private $marketplaceProductRepository;
    private $pricingService;

    public function __construct(MarketplaceProductRepository $marketplaceProductRepository)
    {
        $this->marketplaceProductRepository = $marketplaceProductRepository;
    }

    public function search(MarketplaceProductSearchRequest $searchRequest)
    {
        return MarketplaceProductSearch::apply($searchRequest);
    }

    public function estimate(ProfitEstimateRequest $profitRequest)
    {
        $this->pricingService = new PricingService();
        return $this->pricingService->availableShippingWithProfit($profitRequest);
    }
}
