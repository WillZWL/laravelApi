<?php

namespace App\Services;


use App\Models\AmazonFulfilmentFeeRate;
use App\Models\MarketplaceSkuMapping;
use App\Repository\MarketplaceProductRepository;

class AmazonFbaFeesService
{
    private $marketplaceProductRepository;

    public function __construct(
        MarketplaceProductRepository $marketplaceProductRepository
    ) {
        $this->marketplaceProductRepository = $marketplaceProductRepository;
    }
    public function updateFbaFees($id)
    {
        $marketplaceProduct = $this->marketplaceProductRepository->find($id);
        $amazonFbaFees = $marketplaceProduct->amazonFbaFee;

        $storageFee = $this->calculateStorageFee($marketplaceProduct);
        $amazonFbaFees->storage_fee = $storageFee;

        $weightHandingFee = $this->calculateWeightHandingFee($marketplaceProduct);
        $amazonFbaFees->weight_handing_fee = $weightHandingFee;

        $pickAndPackFee = $this->calculatePickAndPackFee($marketplaceProduct);
        $amazonFbaFees->pick_and_pack_fee = $pickAndPackFee;

        $orderHandingFee = $this->calculateOrderHandingFee($marketplaceProduct);
        $amazonFbaFees->order_handing_fee = $orderHandingFee;

        $amazonFbaFees->save();
    }

    private function calculateStorageFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $length = $marketplaceProduct->product->height;
        $height = $marketplaceProduct->product->length;
        $width = $marketplaceProduct->product->width;

        $volumeInCubicMetre = $length * $height * $width / 1000000;

        $country = $marketplaceProduct->mpControl->country_id;
        if ($country === 'US') {
            $productSize = $marketplaceProduct->amazonProductSizeTier->product_size;
            if (in_array($productSize, [1, 2])) {
                $pricePerCubicFoot = 2.25;  // standard-size
            } else {
                $pricePerCubicFoot = 1.15;  // oversize
            }
        } else {
            $pricePerCubicFoot = 0.4;
        }

        return round($volumeInCubicMetre / 0.0283168466 * $pricePerCubicFoot, 2);
    }

    private function calculateWeightHandingFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $country = $marketplaceProduct->mpControl->country_id;
        $productSize = $marketplaceProduct->amazonProductSizeTier->product_size;
        $unitWeight = $marketplaceProduct->product->weight;
        $weight = $unitWeight;

        if ($country == 'US') {
            $unitWeightInLbs = $unitWeight / 0.4535924;
            $dimensionalWeightInLbs = $marketplaceProduct->product->length
                * $marketplaceProduct->product->width
                * $marketplaceProduct->product->height
                / 166 / 2.54 / 2.54 / 2.54;

            switch ($productSize) {
                case 1:
                case 2:
                    if ($unitWeightInLbs <= 1) {
                        $weight = round($unitWeightInLbs + 0.25) * 0.4535924;
                    } else {
                        $weight = round(max($unitWeightInLbs, $dimensionalWeightInLbs) + 0.25) * 0.4535924;
                    }
                    break;
                case 3:
                case 4:
                case 5:
                    $weight = round(max($unitWeightInLbs, $dimensionalWeightInLbs) + 1) * 0.4535924;
                    break;
                case 6:
                    $weight = round($unitWeightInLbs + 1) * 0.4535924;
                    break;
            }
        }

        $feeRate = AmazonFulfilmentFeeRate::where('country', $country)
            ->where('product_size', $productSize)
            ->where('max_weight_in_kg', '>=', $weight)
            ->orderBy('max_weight_in_kg', 'asc')
            ->first();

        if ($feeRate) {
            $weightHandingFee = round($feeRate->first_fixed_fee + ($weight - $feeRate->first_weight_in_kg) * $feeRate->addition_fee_per_kg, 2);
        } else {
            // TODO
            // can't find the rules
            $weightHandingFee = 9999999;
        }

        return $weightHandingFee;
    }

    private function calculatePickAndPackFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $pickAndPackFee = 0;
        $country = $marketplaceProduct->mpControl->country_id;

        if ($country == 'US') {
            $productSize = $marketplaceProduct->amazonProductSizeTier->product_size;
            switch ($productSize) {
                case 1:
                case 2:
                    $pickAndPackFee = 1.06;
                    break;
                case 3:
                    $pickAndPackFee = 4.09;
                    break;
                case 4:
                    $pickAndPackFee = 5.2;
                    break;
                case 5:
                    $pickAndPackFee = 8.4;
                    break;
                case 6:
                    $pickAndPackFee = 10.53;
                    break;
            }
        }

        return $pickAndPackFee;
    }

    private function calculateOrderHandingFee(MarketplaceSkuMapping $marketplaceProduct)
    {
        $orderHandingFee = 0;
        $country = $marketplaceProduct->mpControl->country_id;

        if ($country == 'US') {
            $productSize = $marketplaceProduct->amazonProductSizeTier->product_size;
            if (in_array($productSize, [1, 2])) {
                $orderHandingFee = 1;
            }
        }

        return $orderHandingFee;
    }

}
