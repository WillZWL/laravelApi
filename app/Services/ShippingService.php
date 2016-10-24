<?php

namespace App\Services;

use App\Models\CourierCost;
use App\Models\CourierInfo;
use App\Models\WeightCourier;
use App\Repository\AcceleratorShippingRepository;
use App\Repository\MarketplaceProductRepository;

class ShippingService
{
    private $shippingRepository;
    private $marketplaceProductRepository;

    public function __construct(
        AcceleratorShippingRepository $acceleratorShippingRepository,
        MarketplaceProductRepository $marketplaceProductRepository
    ) {
        $this->shippingRepository = $acceleratorShippingRepository;
        $this->marketplaceProductRepository = $marketplaceProductRepository;
    }

    public function shippingOptions($id)
    {
        $marketplaceProduct = $this->marketplaceProductRepository->find($id);
        $fromWarehouse = $marketplaceProduct->product->default_ship_to_warehouse;
        if (!$fromWarehouse) {
             $fromWarehouse = $marketplaceProduct->merchant->default_ship_to_warehouse;
        }
        $deliveryCountry = $marketplaceProduct->mpControl->country_id;
        $merchant = $marketplaceProduct->merchant->id;
        $courierOptions = $this->shippingRepository->shippingOptions($merchant, $fromWarehouse, $deliveryCountry);
        $couriers = $courierOptions->pluck('courier_id', 'courier_type');

        $battery = $marketplaceProduct->product->battery;
        $courierInfos = CourierInfo::findMany($couriers)->filter(function ($courierInfo) use ($battery) {
            if ($battery === 1) {
                return ($courierInfo->allow_builtin_battery === 1);
            }
            if ($battery === 2) {
                return ($courierInfo->allow_external_battery === 1);
            }
            return true;
        });

        $weight = $marketplaceProduct->product->weight;
        $volumeWeight = $marketplaceProduct->product->vol_weight;
        $weight = ($weight > $volumeWeight) ? $weight : $volumeWeight;
        $weightId = WeightCourier::where('weight', '>=', $weight)->first()->id;

        $courierCost = $courierInfos->map(function ($courierInfo) use ($deliveryCountry, $weightId) {
            return $courierInfo->courierCost()->where('dest_country_id', $deliveryCountry)
                                                ->where('dest_state_id', '')
                                                ->where('weight_id', $weightId)
                                                ->where('courier_id', $courierInfo->courier_id)
                                                ->first();
        })->reject(function ($courierCost) {
            return empty($courierCost);
        });

        $merchant = $marketplaceProduct->merchant;
        $cost = $courierCost->map(function ($courierCostModel) use ($merchant) {
            $courierCostModel->load('courierInfo')->with('deliveryTypeMapping');
            $deliveryType = $courierCostModel->courierInfo->deliveryTypeMapping->delivery_type;
            $courierCostMarkupInPercent = $merchant->courierCostMarkup()
                    ->where('delivery_type_id', $deliveryType)
                    ->first()
                    ->quotation_percent / 100;
            $surchargeInPercent = $courierCostModel->courierInfo->surcharge / 100;
            $final_delivery_cost = $courierCostModel->delivery_cost * (1 + $surchargeInPercent) * (1 + $courierCostMarkupInPercent);

            return [
                'courierId' => $courierCostModel->courierInfo->courier_id,
                'cost' => $final_delivery_cost,
                'currency' => $courierCostModel->currency_id,
                'deliveryType' => $deliveryType,
            ];
        });

        return $cost;
    }
}
