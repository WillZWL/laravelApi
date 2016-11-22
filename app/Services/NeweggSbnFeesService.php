<?php

namespace App\Services;

use App\Models\AmazonFulfilmentFeeRate;
use App\Models\MarketplaceSkuMapping;

class NeweggSbnFeesService extends FulfilmentByMarketplaceFeesService
{
    public function updateFulfilmentFees($id)
    {
        $marketplaceProduct = $this->marketplaceProductRepository->find($id);
        $sbnFees = $marketplaceProduct->amazonFbaFee;

        $storageFee = $this->calculateStorageFee($marketplaceProduct);
        $sbnFees->storage_fee = $storageFee;

        $orderHandingFee = $this->calculateOrderHandingFee($marketplaceProduct);
        $sbnFees->order_handing_fee = $orderHandingFee;

        $pickAndPackFee = $this->calculatePickAndPackFee($marketplaceProduct);
        $sbnFees->pick_and_pack_fee = $pickAndPackFee;

        $weightHandingFee = $this->calculateWeightHandingFee($marketplaceProduct);
        $sbnFees->weight_handing_fee = $weightHandingFee;

        $sbnFees->save();
    }

    public function calculateStorageFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        // storage fee for SBN is 0 for now, we have 6 months free.
        // TODO: waiting for later requirements
        return 0;
    }

    public function calculateOrderHandingFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $orderHandingFee = 0;
        $productSize = $marketplaceProduct->amazonProductSizeTier->product_size;
        if (in_array($productSize, [14, 15])) {
            $orderHandingFee = 0.95;  // USD
        }

        return $orderHandingFee;
    }

    public function calculatePickAndPackFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $pickAndPackFee = 0;
        $productSize = $marketplaceProduct->amazonProductSizeTier->product_size;
        switch ($productSize) {
            case 14:
            case 15:
                $pickAndPackFee = 0.95; // currency USD
                break;
            case 16:
                $pickAndPackFee = 3.80;
                break;
            case 17:
                $pickAndPackFee = 4.75;
                break;
            case 18:
                $pickAndPackFee = 7.6;
                break;
            case 19:
                $pickAndPackFee = 9.5;
        }

        return $pickAndPackFee;
    }

    public function calculateWeightHandingFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $country = $marketplaceProduct->mpControl->country_id;
        $productSize = $marketplaceProduct->amazonProductSizeTier->product_size;
        $unitWeightInLbs = $marketplaceProduct->product->weight / 0.4535;

        if ($productSize === 19) {
            $shippingWeight = round($unitWeightInLbs) * 0.4535;
        } else {
            $dimensionalWeightInLbs = $marketplaceProduct->product->length
                * $marketplaceProduct->product->width
                * $marketplaceProduct->product->height
                / 166 / 2.54 / 2.54 / 2.54;
            $shippingWeight = round(max($unitWeightInLbs, $dimensionalWeightInLbs)) * 0.4535;
        }

        $feeRate = AmazonFulfilmentFeeRate::where('marketplace', '=', 'NEWEGG')
            ->where('country', $country)
            ->where('product_size', $productSize)
            ->where('max_weight_in_kg', '>=', $shippingWeight)
            ->orderBy('max_weight_in_kg', 'asc')
            ->first();

        if ($feeRate) {
            $weightHandingFee = round($feeRate->first_fixed_fee + ($shippingWeight - $feeRate->first_weight_in_kg) * $feeRate->addition_fee_per_kg, 2);
        } else {
            // TODO
            // can't find the rules
            $weightHandingFee = 9999999;
        }

        return $weightHandingFee;
    }
}

