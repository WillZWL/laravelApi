<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use App\Repository\MarketplaceProductRepository;

abstract  class FulfilmentByMarketplaceFeesService
{
    protected $marketplaceProductRepository;

    public function __construct(
        MarketplaceProductRepository $marketplaceProductRepository
    ) {
        $this->marketplaceProductRepository = $marketplaceProductRepository;
    }

    abstract public function updateFulfilmentFees($id);

    abstract public function calculateStorageFee(MarketplaceSkuMapping $marketplaceProduct);

    abstract public function calculateWeightHandingFee(MarketplaceSkuMapping $marketplaceProduct);

    abstract public function calculatePickAndPackFee(MarketplaceSkuMapping $marketplaceProduct);

    abstract public function calculateOrderHandingFee(MarketplaceSkuMapping $marketplaceProduct);
}
