<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CourierCost;
use App\Models\CourierInfo;
use App\Models\WeightCourier;
use App\Models\So;
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
        $deliveryAddress = Country::find($deliveryCountry);
        $deliveryState = $deliveryAddress->countryState()->where('is_default_state', '=', 1)->first();
        if (empty($deliveryState)) {
            $deliveryStateId = '';
        } else {
            $deliveryStateId = $deliveryState->state_id;
        }

        $merchant = $marketplaceProduct->merchant->id;
        $courierOptions = $this->shippingRepository->shippingOptions($merchant, $fromWarehouse, $deliveryCountry);
        $couriers = $courierOptions->pluck('courier_id', 'courier_type');

        // Lazada only use EXP
        $marketplace = substr($marketplaceProduct->mpControl->marketplace_id, 2);
        if ($marketplace === 'LAZADA') {
            $couriers = $couriers->filter(function ($item, $key) {
                return ($key === 'acc_courier_exp');
            });
        }

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

        $courierCost = $courierInfos->map(function ($courierInfo) use ($deliveryCountry, $deliveryStateId, $weightId) {
            return $courierInfo->courierCost()->where('dest_country_id', $deliveryCountry)
                                                ->where('dest_state_id', $deliveryStateId)
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

        // use cheapest STD
        $uniqueOptions = $cost->sortBy('cost')->unique('deliveryType');

        return $uniqueOptions;
    }


    public function orderDeliveryCost($soNo)
    {
        $order = So::findOrFail($soNo);
        if ($order) {
            $weight = $order->weight;
            $actual_weight = $order->actual_weight;
            $deliveryCountry = $order->delivery_country_id;
            $delivertState = $order->delivery_state;

            $soAllocate = $order->soAllocate()->first();

            $courierId = '';
            if ($soAllocate) {
                $soShipment = $soAllocate->soShipment()->first();
                if ($soShipment) {
                    $courierId = $soShipment->courier_id;
                }
            }
            if ($courierId) {
                $courierInfo = CourierInfo::find($courierId);
                $surcharge = $courierInfo->surcharge;

                if ($weight == 0 || $weight == '') {
                    $soItems = $order->soItem()->get();
                    $total_weight = 0;
                    foreach ($soItems as $soItem) {
                        $weight_in_kg = $soItem->product()->first()->weight;
                        $total_weight += $weight_in_kg * ($soItem->qty) * 1;
                    }
                    $weight = $total_weight;
                }

                $weight = $actual_weight > 0 ? $actual_weight : $weight;
                $weightId = WeightCourier::where('weight', '>=', $weight)->first()->id;

                $courierCost = $courierInfo->courierCost()
                               ->where('weight_id', $weightId)
                               ->where('dest_country_id', $deliveryCountry)
                               ->where('dest_state_id', $delivertState)
                               ->first();
                if (!$courierCost) {
                    $courierCost = $courierInfo->courierCost()
                               ->where('weight_id', $weightId)
                               ->where('dest_country_id', $deliveryCountry)
                               ->where('dest_state_id', '')
                               ->first();
                }

                $currencyId = $courierCost->currency_id;
                $deliveryCost = $courierCost->delivery_cost;

                return [
                    'currency_id' => $currencyId,
                    'delivery_cost' => $deliveryCost,
                    'surcharge' => $surcharge,
                ];
            } else {
                return [
                    'error' => 'The Order Not Allocate Successfully Yet'
                ];
            }
        }
    }
}
