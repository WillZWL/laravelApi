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

    public function __construct(MarketplaceProductRepository $marketplaceProductRepository, PricingService $pricingService)
    {
        $this->marketplaceProductRepository = $marketplaceProductRepository;
        $this->pricingService = $pricingService;
    }

    public function search(MarketplaceProductSearchRequest $searchRequest)
    {
        $products = MarketplaceProductSearch::apply($searchRequest);
        $products->map(function ($product) {
            $profitRequest = new ProfitEstimateRequest();
            $profitRequest->merge([
                'id' => $product->id,
                'selling_price' => $product->price,
            ]);

            $availableShippingWithProfit = $this->estimate($profitRequest);
            $product->available_delivery_type = $availableShippingWithProfit;
            return $product;
        });

        return $products;
    }

    public function estimate(ProfitEstimateRequest $profitRequest)
    {
        return $this->pricingService->availableShippingWithProfit($profitRequest);
    }

    public function update($bulkUpdateRequest)
    {
        $products = $bulkUpdateRequest->input();

        foreach ($products as $product) {
            $this->marketplaceProductRepository->update($product);
        }
    }
}
