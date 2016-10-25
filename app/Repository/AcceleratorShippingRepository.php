<?php

namespace App\Repository;

use App\Models\AcceleratorShipping;
use App\Models\CourierCost;

class AcceleratorShippingRepository
{
    public function shippingOptions($merchantId = 'CASE', $warehouse = 'ES_HK', $deliveryCountry = 'US')
    {
        // TODO
        // need to confirm use merchant ID or 'ALL'
        $merchantId = 'ALL';
        return AcceleratorShipping::where('warehouse', '=', $warehouse)
            ->where('country_id', '=', $deliveryCountry)
            ->where('merchant_id', '=', $merchantId)
            ->where('status', 1)
            ->get();
    }
}
