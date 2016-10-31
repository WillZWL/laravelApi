<?php

namespace App\Services;

use App\Http\Requests\MarketplaceProductSearchRequest;
use App\Http\Requests\ProfitEstimateRequest;
use App\Repository\MarketplaceProductRepository;
use App\Search\MarketplaceProductSearch;
use Excel;

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
        return MarketplaceProductSearch::apply($searchRequest);
    }

    public function searchWithProfit(MarketplaceProductSearchRequest $searchRequest)
    {
        $products = $this->search($searchRequest);
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

    public function export(MarketplaceProductSearchRequest $searchRequest)
    {
        $products = $this->search($searchRequest);

        $formattedProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'marketplace_id' => $product->marketplace_id,
                'country_id' => $product->country_id,
                'master_sku' => ($product->skuMapping) ? $product->skuMapping->ext_sku : 'NO MASTER SKU',
                'marketplace_sku' => $product->marketplace_sku,
                'esg_sku' => $product->sku,
                'product_name' => $product->product->name,
                'sourcing_status' => ($product->supplierProduct) ? $product->supplierProduct->supplier_status : 'NO SUPPLIER COST',
                'selling_price' => $product->price,
                'default_warehouse' => ($product->product->default_ship_to_warehouse) ?: $product->merchantProductMapping->merchant->default_ship_to_warehouse,
                'selected_delivery_type' => $product->delivery_type,
                'listing_status' => $product->listing_status,
                'listing_quantity' => $product->inventory,
                'surplus_quantity' => $product->product->surplus_quantity,
            ];
        });

        $excel = Excel::create('product_inventory_feed', function ($excel) use ($formattedProducts) {
            $excel->sheet('first', function ($sheet) use ($formattedProducts) {
                $sheet->fromArray($formattedProducts);
            });
        });

        return $excel;
    }

    public function update($bulkUpdateRequest)
    {
        $products = $bulkUpdateRequest->input();

        foreach ($products as $product) {
            $this->marketplaceProductRepository->update($product);
        }
    }
}
