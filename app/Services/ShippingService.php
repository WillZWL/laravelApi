<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CourierCost;
use App\Models\CourierInfo;
use App\Models\WeightCourier;
use App\Models\So;
use App\Models\ExchangeRate;
use App\Repository\AcceleratorShippingRepository;
use App\Repository\MarketplaceProductRepository;

class ShippingService
{
    const POSTAGE_OVERWEIGHT_LIMIT = 2;
    const QUOTATION_OVERWEIGHT_LIMIT = 30;

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

        // sbf 10953
        $marketplace = substr($marketplaceProduct->mpControl->marketplace_id, 2);
        if ($marketplace === 'LAZADA') {
            $couriers = $couriers->filter(function ($item, $key) {
                return ($key === 'acc_courier_exp') || ($key === 'acc_courier');
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
        $finalDeliveryCost = null;
        $merchant = $marketplaceProduct->merchant;
        $cost = $courierCost->map(function ($courierCostModel) use ($merchant) {
            $courierCostModel->load('courierInfo')->with('deliveryTypeMapping');
            $deliveryType = $courierCostModel->courierInfo->deliveryTypeMapping->delivery_type;
            $courierCostMarkup = $merchant->courierCostMarkup()
                    ->where('delivery_type_id', $deliveryType)
                    ->first();
            if($courierCostMarkup){
                $courierCostMarkupInPercent = $courierCostMarkup->quotation_percent / 100;
            } else {
                $courierCostMarkupInPercent = 0;
            }
            $surchargeInPercent = $courierCostModel->courierInfo->surcharge / 100;
            $finalDeliveryCost = $courierCostModel->delivery_cost * (1 + $surchargeInPercent) * (1 + $courierCostMarkupInPercent);
            return [
                'courierId' => $courierCostModel->courierInfo->courier_id,
                'cost' => $finalDeliveryCost,
                'currency' => $courierCostModel->currency_id,
                'deliveryType' => $deliveryType,
                ];
        });

        // use cheapest STD
        $uniqueOptions = $cost->sortBy('cost')->unique('deliveryType');

        return $uniqueOptions;
    }

    public function orderDeliveryOptions($soNo)
    {
        $order = So::findOrFail($soNo);
        if ($order) {
            $merchant = $order->sellingPlatform->merchant;
            $deliveryType = $order->delivery_type_id;
            $deliveryCountryId = $order->delivery_country_id;
            $deliveryStateId = $order->delivery_state;
            $currencyId = $order->currency_id;

            $basedOrderProduct = $this->readyBasedOrderProduct($order);

            $basedOrderWeight = $this->readyBasedOrderWeight($order, $basedOrderProduct['prodWeightTotal']);

            $deBug = new \stdClass();
            $deBug->soNo = $order->so_no;
            $deBug->deliveryTypeId = $deliveryType;
            $deBug->deliveryCountryId = $deliveryCountryId;
            $deBug->deliveryTypeId = $deliveryType;
            $deBug->deliveryStateId = $deliveryStateId;
            $deBug->currencyId = $currencyId;
            $deBug->basedOrderProduct = $basedOrderProduct;
            $deBug->basedOrderWeight = $basedOrderWeight;

            $masterShipping = $this->readyBasedMasterShipping($basedOrderProduct, $deliveryType, $deliveryCountryId);

            $containExternal = $basedOrderProduct['external'];
            $containBuiltIn = $basedOrderProduct['builtIn'];
            $courierInfos = CourierInfo::findMany($masterShipping['masterShippingCouriers'])
            ->filter(function ($courierInfo) use ($containExternal, $containBuiltIn) {
                if ($containExternal !== true && $containBuiltIn === true) {
                    return ($courierInfo->allow_builtin_battery === 1);
                }

                if ($containExternal === true && $containBuiltIn !== true) {
                    return ($courierInfo->allow_external_battery === 1);
                }

                if ($containExternal === true && $containBuiltIn === true) {
                    return (($courierInfo->allow_builtin_battery === 1)
                            && ($courierInfo->allow_external_battery === 1));
                }

                return true;
            });

            $weightId = $basedOrderWeight['weightId'];
            $isOverweight = $basedOrderWeight['isOverweight'];
            $courierCost = $courierInfos->map(function ($courierInfo) use ($deliveryCountryId, $deliveryStateId, $weightId) {
                $results = $courierInfo->courierCost()->where('dest_country_id', $deliveryCountryId)
                                                    ->where('dest_state_id', $deliveryStateId)
                                                    ->where('weight_id', $weightId)
                                                    ->where('courier_id', $courierInfo->courier_id)
                                                    ->first();
                if (! isset($results)) {
                    $results = $courierInfo->courierCost()->where('dest_country_id', $deliveryCountryId)
                                                    ->where('dest_state_id', '')
                                                    ->where('weight_id', $weightId)
                                                    ->where('courier_id', $courierInfo->courier_id)
                                                    ->first();
                }

                return $results;
            })->reject(function ($courierCost) {
                return empty($courierCost);
            });

            $costCollect = $courierCost->map(function ($courierCostModel) use ($merchant, $deliveryType, $basedOrderWeight, $currencyId) {
                $courierCostMarkup = $merchant->courierCostMarkup()
                ->where('delivery_type_id', $deliveryType)
                ->first();

                if ($courierCostMarkup) {
                    $courierCostMarkupInPercent = $courierCostMarkup->quotation_percent / 100;
                } else {
                    $courierCostMarkupInPercent = 0;
                }

                $surchargeInPercent = $courierCostModel->courierInfo->surcharge / 100;

                $isOverweight = $basedOrderWeight['isOverweight'];
                $weightUsed = $basedOrderWeight['weightUsed'];
                if ($isOverweight !== true) {
                    $deliveryCost = $courierCostModel->delivery_cost;
                } else {
                    $costPerKg = $courierCostModel->cost_per_kg;
                    $deliveryCost = $costPerKg * $weightUsed;
                }

                $fromCurrency = $courierCostModel->currency_id;
                $toCurrency = $currencyId;
                $rate = ExchangeRate::getRate($fromCurrency, $toCurrency);

                $finalDeliveryCost = $deliveryCost * (1 + $courierCostMarkupInPercent) * $rate;
                $finalCost = round($finalDeliveryCost * (1 + $surchargeInPercent), 2);

                return [
                    'id' => $courierCostModel->id,
                    'courierId' => $courierCostModel->courierInfo->courier_id,
                    'deliveryCost' => round($finalDeliveryCost, 2),
                    'cost' => $finalCost,
                    'currency' => $toCurrency,
                    'rate' => $rate,
                    'deliveryType' => $deliveryType,
                    'firstSurcharge' => $courierCostModel->courierInfo->surcharge,
                    'firstSurchargeInPercent' => $surchargeInPercent,
                    'courierCostMarkupInPercent' => $courierCostMarkupInPercent,
                ];
            })->reject(function ($costCollect) {
                return empty($costCollect);
            });

            $recMinCost =$costCollect->min('cost');

            $quotation = [];

            if (isset($recMinCost)) {
                foreach ($costCollect as $delivery) {
                    if ($delivery['cost'] == $recMinCost) {
                        $quotation = $delivery;
                    }
                }
            }

            $deBug->masterShipping = $masterShipping;
            $deBug->deliveryCost = $costCollect;
            $deBug->recommendMinCost = $recMinCost;
            $deBug->quotation = $quotation;

            return [
                'quotation' => $quotation,
                'deBug' => $deBug,
            ];
        }
    }

    public function readyBasedMasterShipping($basedOrderProduct, $deliveryType, $deliveryCountryId)
    {
        $defaultShipToWarehouse = $this->shippingRepository->getShipToWarehouse($basedOrderProduct['skuCollect']);
        $quotationTypes = $this->shippingRepository->getQuotationTypes($deliveryType);
        $masterShippingCouriers = $this->shippingRepository->shippingCouriers(
            $defaultShipToWarehouse,
            $quotationTypes,
            $deliveryCountryId
        );

        return [
            'defaultShipToWarehouse' => $defaultShipToWarehouse,
            'quotationTypes' => $quotationTypes,
            'masterShippingCouriers' => $masterShippingCouriers,
            // 'courierInfos' => $courierInfos,
        ];
    }

    public function readyBasedOrderProduct($order)
    {
        $builtIn = false;
        $external = false;
        $skuCollect = [];
        $prodWeightTotal = 0;
        $soItems = $order->soItem()->where('hidden_to_client', '=', 0)->get();
        foreach ($soItems as $soItem) {
            $skuCollect[] = $soItem->prod_sku;
            if ($soItem->product->battery === 1) {
                $builtIn = true;
            }
            if ($soItem->product->battery === 2) {
                $external = true;
            }
            $prodWeightTotal += $soItem->product->weight;
        }

        return [
            'builtIn' => $builtIn,
            'external' => $external,
            'skuCollect' => $skuCollect,
            'prodWeightTotal' => $prodWeightTotal,
        ];
    }

    public function readyBasedOrderWeight($order, $prodWeightTotal = 0)
    {
        $isOverweight = false;
        $deliveryType = $order->delivery_type_id;
        $volWeight = $order->vol_weight;
        $weight = $order->weight;
        $weight = ($weight == 0) ? $prodWeightTotal : $weight;
        $weightUsed = $weight;

        switch ($deliveryType) {
            case 'STD':
                if ($weightUsed > self::POSTAGE_OVERWEIGHT_LIMIT) {
                    # STD weight > 2 cannot allow take weightId
                    $weightUsed = null;

                    $isOverweight = true;
                    $weightId = 0;
                }
                break;

            default:
                if($volWeight > $weightUsed) {
                    $weightUsed = $volWeight;
                }

                break;
        }

        if ($weightUsed && $weightUsed <= self::QUOTATION_OVERWEIGHT_LIMIT) {
            if ($weightCourier = WeightCourier::where('weight', '>=', $weightUsed)->first()) {
                $weightId = $weightCourier->id;
            } else {
                $weightId = 0;
            }
            // $weightId = WeightCourier::where('weight', '>=', $weightUsed)->first()->id;
        } else if ($weightUsed && $weightUsed > self::QUOTATION_OVERWEIGHT_LIMIT) {
            $isOverweight = true;
            $weightId = 1;
        }

        return [
            'volWeight' => $volWeight,
            'weight' => $weight,
            'weightUsed' => $weightUsed,
            'weightId' => $weightId,
            'isOverweight' => $isOverweight,
        ];
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
