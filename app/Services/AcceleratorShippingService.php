<?php

namespace App\Services;

use App\Http\Requests\AcceleratorShippingRequest;
use App\Repository\AcceleratorShippingRepository;

class AcceleratorShippingService
{
    private $acceleratorShippingRepository;

    public function __construct(AcceleratorShippingRepository $acceleratorShippingRepository)
    {
        $this->acceleratorShippingRepository = $acceleratorShippingRepository;
    }

    public function getShippingOptions(AcceleratorShippingRequest $request)
    {
        $merchantId = 'ALL';
        $warehouse = '';
        $destinationCountry = $request->input('country');

        $options = $this->acceleratorShippingRepository->shippingOptions($merchantId, $warehouse, $destinationCountry);

        $data = $options->groupBy('warehouse')->map(function ($option) {
            return $option->pluck('courier_id');
        });

        return $data;
    }
}
