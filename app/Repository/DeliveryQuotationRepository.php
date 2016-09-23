<?php

namespace App\Repository;

use App\Models\MarketplaceSkuMapping;
use App\Models\Quotation;
use App\Models\WeightCourier;
use App\Models\CountryState;
use App\Models\ExchangeRate;

// TODO
// delivery quotation 不应每个 sku 都计算一次, 应当只与 merchant/weight/battery/destination 有关.
class DeliveryQuotationRepository
{
    private $destination;
    private $adjustRate = 0.9725;
    private $exchangeRate;

    public function getQuotationCost(MarketplaceSkuMapping $marketplaceProduct)
    {
        $this->destination = CountryState::firstOrNew([
            'country_id' => $marketplaceProduct->mpControl->country_id,
            'is_default_state' => 1,
        ]);
        if ($this->destination->state_id === null) {
            $this->destination->state_id = '';
        }

        $this->exchangeRate = ExchangeRate::whereFromCurrencyId('HKD')
            ->whereToCurrencyId($marketplaceProduct->mpControl->currency_id)
            ->firstOrFail();

        $quotation = new Quotation();
        $quotationVersion = $quotation->getAcceleratorQuotationByProduct($marketplaceProduct->product);

        $actualWeight = WeightCourier::getWeightId($marketplaceProduct->product->weight);
        $volumeWeight = WeightCourier::getWeightId($marketplaceProduct->product->vol_weight);
        $battery = $marketplaceProduct->product->battery;

        if ($battery == 1) {
            $quotationVersion->forget('acc_external_postage');
        }

        $quotation = collect();
        foreach ($quotationVersion as $quotationType => $quotationVersionId) {
            if (substr($marketplaceProduct->marketplace_id, 2) === 'LAZADA' && $quotationType !== 'acc_courier_exp') {
                continue;
            }
            if (($quotationType == 'acc_builtin_postage') || ($quotationType == 'acc_external_postage')) {
                $weight = $actualWeight;
            } else {
                $weight = max($actualWeight, $volumeWeight);
            }

            $quotationItem = Quotation::getQuotation($this->destination, $weight, $quotationVersionId);
            if ($quotationItem) {
                $quotation->push($quotationItem);
            }
        }

        // 已选中的 courier 如果不支持 battery 则 pass.
        $availableQuotation = $quotation->filter(function ($quotationItem) use ($battery) {
            switch ($battery) {
                case '1':
                    if (!$quotationItem->courierInfo->allow_builtin_battery) {
                        return false;
                    }
                    break;

                case '2':
                    if (!$quotationItem->courierInfo->allow_external_battery) {
                        return false;
                    }
                    break;
            }

            return true;
        });

        // convert HKD to target currency.
        $currencyRate = $this->exchangeRate->rate;
        $adjustRate = $this->adjustRate;

        $quotationCost = $availableQuotation->map(function ($item) use ($currencyRate, $adjustRate) {
            $item->cost = round($item->cost * $currencyRate / $adjustRate, 2);

            return $item;
        })->pluck('cost', 'quotation_type');

        // if $quotation contains both built-in and external quotation, choose the cheapest quotation.
        if ($quotationCost->has('acc_builtin_postage') && $quotationCost->has('acc_external_postage')) {
            if ($quotationCost->get('acc_builtin_postage') > $quotationCost->get('acc_external_postage')) {
                $quotationCost->forget('acc_builtin_postage');
            } else {
                $quotationCost->forget('acc_external_postage');
            }
        }

        // convert quotation type to delivery type
        $freightCost = collect();
        $quotationCost->map(function ($cost, $quotationType) use ($freightCost) {
            switch ($quotationType) {
                case 'acc_builtin_postage':
                case 'acc_external_postage':
                    $freightCost->put('STD', collect(['deliveryCost' => $cost]));
                    break;
                case 'acc_courier':
                    $freightCost->put('EXPED', collect(['deliveryCost' => $cost]));
                    break;
                case 'acc_courier_exp':
                    $freightCost->put('EXP', collect(['deliveryCost' => $cost]));
                    break;
                case 'acc_fba':
                    $freightCost->put('FBA', collect(['deliveryCost' => $cost]));
                    break;
                case 'acc_mcf':
                    $freightCost->put('MCF', collect(['deliveryCost' => $cost]));
            }
        });

        return $freightCost;
    }
}
