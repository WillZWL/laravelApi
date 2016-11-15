<?php

namespace App\Services;

use App\Models\MarketplaceSkuMapping;
use App\Repository\AmazonProductSizeTierRepository;
use App\Repository\MarketplaceProductRepository;

class AmazonProductSizeTierService
{
    const SMALL_STANDARD_SIZE_IN_US = 1;
    const LARGE_STANDARD_SIZE_IN_US = 2;
    const SMALL_OVERSIZE_IN_US = 3;
    const MEDIUM_OVERSIZE_IN_US = 4;
    const LARGE_OVERSIZE_IN_US = 5;
    const SPECIAL_OVERSIZE_IN_US = 6;

    const SMALL_ENVELOPE_IN_EU = 7;
    const STANDARD_ENVELOPE_IN_EU = 8;
    const LARGE_ENVELOPE_IN_EU = 9;
    const STANDARD_PARCEL_IN_EU = 10;
    const SMALL_OVERSIZE_IN_EU = 11;
    const STANDARD_OVERSIZE_IN_EU = 12;
    const LARGE_OVERSIZE_IN_EU = 13;

    const UNKNOWN_SIZE = 77;

    private $marketplaceProductRepository;
    private $amazonProductSizeTierRepository;

    public function __construct(
        MarketplaceProductRepository $marketplaceProductRepository,
        AmazonProductSizeTierRepository $amazonProductSizeTierRepository
    ) {
        $this->marketplaceProductRepository = $marketplaceProductRepository;
        $this->amazonProductSizeTierRepository = $amazonProductSizeTierRepository;
    }

    public function updateProductSizeTier($id)
    {
        $marketplaceProduct = $this->marketplaceProductRepository->find($id);

        $productSize = $this->getProductSize($marketplaceProduct);

        $marketplaceProduct->amazonProductSizeTier->product_size = $productSize;
        $marketplaceProduct->amazonProductSizeTier->save();
    }

    public function getProductSize(MarketplaceSkuMapping $marketplaceProduct)
    {
        $country = $marketplaceProduct->mpControl->country_id;

        // TODO
        // hard code first, need add one field to identified product is media or not.
        $media = 0;

        $dimensions = [
            $marketplaceProduct->product->length,
            $marketplaceProduct->product->width,
            $marketplaceProduct->product->height,
        ];
        sort($dimensions);
        list($shortestSide, $medianSide, $longestSide) = $dimensions;
        $weight = $marketplaceProduct->product->weight;

        if (in_array($country, ['GB', 'FR', 'DE', 'ES', 'IT'])) {
            $productSizeTier = $this->calculateProductSizeTierInAmazonEu($weight, $longestSide, $medianSide, $shortestSide);
        } elseif ($country === 'US') {
            $dimensionalWeight = $longestSide * $medianSide * $shortestSide / 166;
            $weight = ($weight > $dimensionalWeight) ? $weight : $dimensionalWeight;
            $productSizeTier = $this->calculateProductSizeTierInAmazonUs($media, $weight, $longestSide, $medianSide, $shortestSide);
        } else {
            // TODO
            // calculate product size tier in other countries.
            $productSizeTier = self::UNKNOWN_SIZE;
        }

        return $productSizeTier;
    }

    public function calculateProductSizeTierInAmazonUs($media, $weight, $longestSide, $medianSide, $shortestSide)
    {
        $smallStandardSizeRulesOne = [
            'media' => 0,
            'weight' => 12 * 0.0283495, // oz to kg
            'longestSide' => 15 * 2.54, // inch to cm
            'medianSide' => 12 * 2.54,
            'shortestSide' => 0.75 * 2.54,
        ];

        $smallStandardSizeRulesTwo = [
            'media' => 1,
            'weight' => 14 * 0.0283495, // oz to kg
            'longestSide' => 15 * 2.54, // inch to cm
            'medianSide' => 12 * 2.54,
            'shortestSide' => 0.75 * 2.54,
        ];

        $largeStandardSizeRules = [
            'weight' => 20 * 0.4535924, // lb to kg
            'longestSide' => 18 * 2.54, // inch to cm
            'medianSide' => 14 * 2.54,
            'shortestSide' => 8 * 2.54,
        ];

        $smallOversizeRules = [
            'weight' => 70 * 0.4535924, // lb to kg
            'longestSide' => 60 * 2.54, // inch to cm
            'medianSide' => 30 * 2.54,
            'lengthPlusGirth' => 130 * 2.54,
        ];

        $mediumOversizeRules = [
            'lengthPlusGirth' => 130 * 2.54,
            'weight' => 150 * 0.4535924, // lb to kg
            'longestSide' => 108 * 2.54, // inch to cm
        ];

        $largeOversizeRules = [
            'lengthPlusGirth' => 160 * 2.54,
            'weight' => 150 * 0.4535924, // lb to kg
            'longestSide' => 108 * 2.54, // inch to cm
        ];

        if ($this->checkProductSizeRulesInUs($smallStandardSizeRulesOne, $media, $weight, $longestSide, $medianSide, $shortestSide)
            || $this->checkProductSizeRulesInUs($smallStandardSizeRulesTwo, $media, $weight, $longestSide, $medianSide, $shortestSide)
        ) {

            return self::SMALL_STANDARD_SIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($largeStandardSizeRules, $media, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_STANDARD_SIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($smallOversizeRules, $media, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_OVERSIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($mediumOversizeRules, $media, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::MEDIUM_OVERSIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($largeOversizeRules, $media, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_OVERSIZE_IN_US;
        }

        // default return special oversize
        return self::SPECIAL_OVERSIZE_IN_US;
    }

    public function calculateProductSizeTierInAmazonEu($weight, $longestSide, $medianSide, $shortestSide)
    {
        // get product size tier from  https://go.amazonservices.com/2015-EU-FBA-Fee-Update.html
        $smallEnvelope = [
            'weight' => (100 - 20) / 1000,      // unit weight = outbound shipping weight - packaging weight
            'longestSide' => 20,
            'medianSide' => 15,
            'shortestSide' => 1,
        ];

        $standardEnvelope = [
            'weight' => (500 - 40) / 1000,      // unit weight = outbound shipping weight - packaging weight
            'longestSide' => 33,
            'medianSide' => 23,
            'shortestSide' => 2.5,
        ];

        $largeEnvelope = [
            'weight' => (1000 - 40) / 1000,      // unit weight = outbound shipping weight - packaging weight
            'longestSide' => 33,
            'medianSide' => 23,
            'shortestSide' => 5,
        ];

        $standardParcel = [
            'weight' => (12000 - 100) / 1000,      // unit weight = outbound shipping weight - packaging weight
            'longestSide' => 45,
            'medianSide' => 34,
            'shortestSide' => 26,
        ];

        $smallOversize = [
            'weight' => (2000- 240) / 1000,      // unit weight = outbound shipping weight - packaging weight
            'longestSide' => 61,
            'medianSide' => 46,
            'shortestSide' => 46,
        ];

        $standardOversize = [
            'weight' => (30000- 240) / 1000,      // unit weight = outbound shipping weight - packaging weight
            'longestSide' => 120,
            'medianSide' => 60,
            'shortestSide' => 60,
        ];

        if ($this->checkProductSizeRulesInEu($smallEnvelope, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_ENVELOPE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($standardEnvelope, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::STANDARD_ENVELOPE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($largeEnvelope, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_ENVELOPE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($standardParcel, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::STANDARD_PARCEL_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($smallOversize, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_OVERSIZE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($standardOversize, $weight, $longestSide, $medianSide, $shortestSide)) {
            return self::STANDARD_OVERSIZE_IN_EU;
        }

        // default return Large oversize
        return self::LARGE_OVERSIZE_IN_EU;
    }

    public function checkProductSizeRulesInEu($rules, $weight, $longestSide, $medianSide, $shortestSide)
    {
        if (isset($rules['weight']) && ($rules['weight'] < $weight)) {
            return false;
        }

        if (isset($rule['longestSide']) && $rule['longestSide'] < $longestSide) {
            return false;
        }

        if (isset($rule['medianSide']) && $rule['medianSide'] < $medianSide) {
            return false;
        }

        if (isset($rule['shortestSide']) && $rule['shortestSide'] < $shortestSide) {
            return false;
        }

        return true;
    }

    public function checkProductSizeRulesInUs($rule, $media, $weight, $longestSide, $medianSide, $shortestSide)
    {
        if (isset($rule['media']) && $rule['media'] != $media) {
            return false;
        }

        if (isset($rule['weight']) && $rule['weight'] < $weight) {
            return false;
        }

        if (isset($rule['longestSide']) && $rule['longestSide'] < $longestSide) {
            return false;
        }

        if (isset($rule['medianSide']) && $rule['medianSide'] < $medianSide) {
            return false;
        }

        if (isset($rule['shortestSide']) && $rule['shortestSide'] < $shortestSide) {
            return false;
        }

        if (isset($rule['lengthPlusGirth']) && $rule['lengthPlusGirth'] < ($longestSide + 2 * ($medianSide + $shortestSide))) {
            return false;
        }

        return true;
    }
}
