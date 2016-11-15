<?php

namespace App\Services;


use App\Models\MarketplaceSkuMapping;
use App\Repository\AmazonProductSizeTierRepository;
use App\Repository\MarketplaceProductRepository;

class AmazonProductSizeTierService
{
    const SMALL_STANDARD_SIZE = 1;
    const LARGE_STANDARD_SIZE = 2;
    const SMALL_OVERSIZE = 3;
    const MEDIUM_OVERSIZE = 4;
    const LARGE_OVERSIZE = 5;
    const SPECIAL_OVERSIZE = 6;

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
        $country = $marketplaceProduct->mpControl->country;

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
        $dimensionalWeight = $longestSide * $medianSide * $shortestSide / 166;

        $weight = ($weight > $dimensionalWeight) ? $weight : $dimensionalWeight;
        $productSizeTier = $this->calculateProductSizeTierInAmazonUs($media, $weight, $longestSide, $medianSide, $shortestSide);

        return $productSizeTier;
    }

    public function calculateProductSizeTierInAmazonUs($media, $weight, $longestSide, $medianSide, $shortSide)
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

        if ($this->checkProductSizeRules($smallStandardSizeRulesOne, $media, $weight, $longestSide, $medianSide, $shortSide)
            || $this->checkProductSizeRules($smallStandardSizeRulesTwo, $media, $weight, $longestSide, $medianSide, $shortSide)
        ) {

            return self::SMALL_STANDARD_SIZE;
        }

        if ($this->checkProductSizeRules($largeStandardSizeRules, $media, $weight, $longestSide, $medianSide, $shortSide)) {
            return self::LARGE_STANDARD_SIZE;
        }

        if ($this->checkProductSizeRules($smallOversizeRules, $media, $weight, $longestSide, $medianSide, $shortSide)) {
            return self::SMALL_OVERSIZE;
        }

        if ($this->checkProductSizeRules($mediumOversizeRules, $media, $weight, $longestSide, $medianSide, $shortSide)) {
            return self::MEDIUM_OVERSIZE;
        }

        if ($this->checkProductSizeRules($largeOversizeRules, $media, $weight, $longestSide, $medianSide, $shortSide)) {
            return self::LARGE_OVERSIZE;
        }

        // default return special oversize
        return self::SPECIAL_OVERSIZE;
    }

    public function checkProductSizeRules($rule, $media, $weight, $longestSide, $medianSide, $shortSide)
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

        if (isset($rule['shortestSide']) && $rule['shortestSide'] < $shortSide) {
            return false;
        }

        if (isset($rule['lengthPlusGirth']) && $rule['lengthPlusGirth'] < ($longestSide + 2 * ($medianSide + $shortSide))) {
            return false;
        }

        return true;
    }
}
