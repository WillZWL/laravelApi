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

    const SMALL_STANDARD_SIZE_IN_NEWEGG_US = 14;
    const LARGE_STANDARD_SIZE_IN_NEWEGG_US = 15;
    const SMALL_OVERSIZE_IN_NEWEGG_US = 16;
    const MEDIUM_OVERSIZE_IN_NEWEGG_US = 17;
    const LARGE_OVERSIZE_IN_NEWEGG_US = 18;
    const SPECIAL_OVERSIZE_IN_NEWEGG_US = 19;

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
        $productSizeTier = self::UNKNOWN_SIZE;

        $marketplace = $marketplaceProduct->mpControl->marketplace_type;
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
        $unitWeight = $marketplaceProduct->product->weight;
        $dimensionalWeight = $longestSide * $medianSide * $shortestSide / 166 / 2.54 / 2.54 / 2.54 * 0.4535924;

        if ($marketplace === 'AMAZON') {
            if (in_array($country, ['GB', 'FR', 'DE', 'ES', 'IT'])) {
                $productSizeTier = $this->calculateProductSizeTierInAmazonEu($unitWeight, $longestSide, $medianSide, $shortestSide);
            } elseif ($country === 'US') {
                // cm / inch = 2.54, lb * 0.4535924 = kg, 166 is const
                $productSizeTier = $this->calculateProductSizeTierInAmazonUs($media, $unitWeight, $dimensionalWeight, $longestSide, $medianSide, $shortestSide);
            }
        } elseif ($marketplace === 'NEWEGG') {
            $productSizeTier = $this->calculateProductSizeTierInNeweggUs($unitWeight, $dimensionalWeight, $longestSide, $medianSide, $shortestSide);
        }


        return $productSizeTier;
    }

    public function calculateProductSizeTierInAmazonUs($media, $unitWeight, $dimensionalWeight, $longestSide, $medianSide, $shortestSide)
    {
        $maxWeight = max($unitWeight, $dimensionalWeight);

        $smallStandardSizeRulesOne = [
            'media' => 0,
            'weight' =>  0.3401940,         // 12 * 0.0283495, // oz to kg
            'longestSide' => 38.10,         // 15 * 2.54, // inch to cm
            'medianSide' => 30.48,          // 12 * 2.54,
            'shortestSide' => 1.9050,       // 0.75 * 2.54,
        ];

        $smallStandardSizeRulesTwo = [
            'media' => 1,
            'weight' => 0.3968930,          // 14 * 0.0283495, // oz to kg
            'longestSide' => 38.10,         // 15 * 2.54, // inch to cm
            'medianSide' => 30.48,          // 12 * 2.54,
            'shortestSide' => 1.9050,       // 0.75 * 2.54,
        ];

        $largeStandardSizeRules = [
            'weight' => 9.0718480,          // 20 * 0.4535924, // lb to kg
            'longestSide' => 45.72,         // 18 * 2.54, // inch to cm
            'medianSide' => 35.56,          // 14 * 2.54,
            'shortestSide' => 20.32,        // 8 * 2.54,
        ];

        $smallOversizeRules = [
            'weight' => 31.7514680,         // 70 * 0.4535924,     // lb to kg
            'longestSide' => 152.40,        // 60 * 2.54, // inch to cm
            'medianSide' => 76.20,          // 30 * 2.54,
            'lengthPlusGirth' => 330.20,    // 130 * 2.54,
        ];

        $mediumOversizeRules = [
            'lengthPlusGirth' => 330.20,    // 130 * 2.54,
            'weight' => 68.0388600,         // 150 * 0.4535924, // lb to kg
            'longestSide' => 274.32,        // 108 * 2.54, // inch to cm
        ];

        $largeOversizeRules = [
            'lengthPlusGirth' => 406.40,    // 160 * 2.54,
            'weight' => 68.0388600,         // 150 * 0.4535924, // lb to kg
            'longestSide' => 274.32,        // 108 * 2.54, // inch to cm
        ];

        if ($this->checkProductSizeRulesInUs($smallStandardSizeRulesOne, $media, $unitWeight, $longestSide, $medianSide, $shortestSide)
            || $this->checkProductSizeRulesInUs($smallStandardSizeRulesTwo, $media, $unitWeight, $longestSide, $medianSide, $shortestSide)
        ) {

            return self::SMALL_STANDARD_SIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($largeStandardSizeRules, $media, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_STANDARD_SIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($smallOversizeRules, $media, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_OVERSIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($mediumOversizeRules, $media, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::MEDIUM_OVERSIZE_IN_US;
        }

        if ($this->checkProductSizeRulesInUs($largeOversizeRules, $media, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_OVERSIZE_IN_US;
        }

        // default return special oversize
        return self::SPECIAL_OVERSIZE_IN_US;
    }

    public function calculateProductSizeTierInAmazonEu($unitWeight, $longestSide, $medianSide, $shortestSide)
    {
        // get product size tier from  https://go.amazonservices.com/2015-EU-FBA-Fee-Update.html
        $smallEnvelope = [
            'weight' => 0.080,                  // (100 - 20) / 1000, unit weight = outbound shipping weight - packaging weight
            'longestSide' => 20,
            'medianSide' => 15,
            'shortestSide' => 1,
        ];

        $standardEnvelope = [
            'weight' => 0.4600,                 // (500 - 40) / 1000, unit weight = outbound shipping weight - packaging weight
            'longestSide' => 33,
            'medianSide' => 23,
            'shortestSide' => 2.5,
        ];

        $largeEnvelope = [
            'weight' => 0.9600,                 // (1000 - 40) / 1000, unit weight = outbound shipping weight - packaging weight
            'longestSide' => 33,
            'medianSide' => 23,
            'shortestSide' => 5,
        ];

        $standardParcel = [
            'weight' => 11.9000,                // (12000 - 100) / 1000, unit weight = outbound shipping weight - packaging weight
            'longestSide' => 45,
            'medianSide' => 34,
            'shortestSide' => 26,
        ];

        $smallOversize = [
            'weight' => 1.7600,                 // (2000- 240) / 1000, unit weight = outbound shipping weight - packaging weight
            'longestSide' => 61,
            'medianSide' => 46,
            'shortestSide' => 46,
        ];

        $standardOversize = [
            'weight' => 29.7600,                // (30000- 240) / 1000, unit weight = outbound shipping weight - packaging weight
            'longestSide' => 120,
            'medianSide' => 60,
            'shortestSide' => 60,
        ];

        if ($this->checkProductSizeRulesInEu($smallEnvelope, $unitWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_ENVELOPE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($standardEnvelope, $unitWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::STANDARD_ENVELOPE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($largeEnvelope, $unitWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_ENVELOPE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($standardParcel, $unitWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::STANDARD_PARCEL_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($smallOversize, $unitWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_OVERSIZE_IN_EU;
        }

        if ($this->checkProductSizeRulesInEu($standardOversize, $unitWeight, $longestSide, $medianSide, $shortestSide)) {
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

        if (isset($rules['longestSide']) && $rules['longestSide'] < $longestSide) {
            return false;
        }

        if (isset($rules['medianSide']) && $rules['medianSide'] < $medianSide) {
            return false;
        }

        if (isset($rules['shortestSide']) && $rules['shortestSide'] < $shortestSide) {
            return false;
        }

        return true;
    }

    public function checkProductSizeRulesInUs($rules, $media, $weight, $longestSide, $medianSide, $shortestSide)
    {
        if (isset($rules['media']) && $rules['media'] != $media) {
            return false;
        }

        if (isset($rules['weight']) && $rules['weight'] < $weight) {
            return false;
        }

        if (isset($rules['longestSide']) && $rules['longestSide'] < $longestSide) {
            return false;
        }

        if (isset($rules['medianSide']) && $rules['medianSide'] < $medianSide) {
            return false;
        }

        if (isset($rules['shortestSide']) && $rules['shortestSide'] < $shortestSide) {
            return false;
        }

        if (isset($rules['lengthPlusGirth']) && $rules['lengthPlusGirth'] < ($longestSide + 2 * ($medianSide + $shortestSide))) {
            return false;
        }

        return true;
    }

    public function calculateProductSizeTierInNeweggUs($unitWeight, $dimensionalWeight, $longestSide, $medianSide, $shortestSide)
    {
        $maxWeight = max($unitWeight, $dimensionalWeight);

        $smallStandardSizeRules = [
            'weight' => 0.453308505,            // 15.99 * 0.0283495, oz to kg
            'longestSide' => 36.1950,           // 14.25 * 2.54, inch to cm
            'medianSide' => 24.130,             // 9.5 * 2.54,
            'shortestSide' => 1.9050,           // 0.75 * 2.54,
        ];

        $largeStandardSizeRules = [
            'weight' => 9.0718480,              // 20 * 0.4535924, lb to kg
            'longestSide' => 63.50,             // 25 * 2.54, // inch to cm
            'medianSide' => 43.18,              // 17 * 2.54,
            'shortestSide' => 30.48             // 12 * 2.54,
        ];

        $smallOversizeRules = [
            'weight' => 31.7514680,             // 70 * 0.4535924, lb to kg
            'longestSide' => 152.40,            // 60 * 2.54, // inch to cm
            'medianSide' => 76.20,              // 30 * 2.54,
            'lengthPlusGirth' => 330.20,        // 130 * 2.54,
        ];

        $mediumOversizeRules = [
            'weight' => 40.8233160,             // 90 * 0.4535924, // lb to kg
            'longestSide' => 274.32,            // 274.32108 * 2.54, // inch to cm
            'lengthPlusGirth' => 330.20,        // 130 * 2.54,
        ];

        $largeOversizeRules = [
            'weight' => 68.0388600,             // 150 * 0.4535924, // lb to kg
            'longestSide' => 274.32,            // 108 * 2.54, // inch to cm
            'lengthPlusGirth' => 419.10,        // 165 * 2.54,
        ];

        if ($this->checkProductSizeRulesInEu($smallStandardSizeRules, $unitWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_STANDARD_SIZE_IN_NEWEGG_US;
        }

        if ($this->checkProductSizeRulesInEu($largeStandardSizeRules, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_STANDARD_SIZE_IN_NEWEGG_US;
        }

        if ($this->checkProductSizeRulesInEu($smallOversizeRules, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::SMALL_OVERSIZE_IN_NEWEGG_US;
        }

        if ($this->checkProductSizeRulesInEu($mediumOversizeRules, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::MEDIUM_OVERSIZE_IN_NEWEGG_US;
        }

        if ($this->checkProductSizeRulesInEu($largeOversizeRules, $maxWeight, $longestSide, $medianSide, $shortestSide)) {
            return self::LARGE_OVERSIZE_IN_NEWEGG_US;
        }

        // default return special oversize
        return self::SPECIAL_OVERSIZE_IN_NEWEGG_US;
    }
}
