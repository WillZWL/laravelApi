<?php

namespace App\Repository;

use App\Models\CourierCost;
use App\Models\WeightCourier;

class CourierCostRepository
{
    const MAX_WEIGHT = 30;

    public function getCourierCost($courier, $deliveryCountry, $deliveryState, $weight)
    {
        if ($weight <= self::MAX_WEIGHT) {
            $weightId = WeightCourier::where('weight', '>=', $weight)->first()->id;
        } else {
            $weightId = 1;
        }

        $courierCostModel = CourierCost::where('dest_country_id', $deliveryCountry)
            ->wehre('dest_state_id', $deliveryState)
            ->where('weight_id', $weightId)
            ->wehre('courier_id', $courier)
            ->first();

        return [
            'cost' => $courierCostModel->delivery_cost,
            'currency' => $courierCostModel->currency,
        ];
    }
}
