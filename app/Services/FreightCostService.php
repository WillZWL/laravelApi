<?php

namespace App\Services;

use App\Http\Requests\FreightCostRequest;
use App\Repository\CourierCostRepository;

class FreightCostService
{
    private $courierCostRepository;

    public function __construct(CourierCostRepository $courierCostRepository)
    {
        $this->courierCostRepository = $courierCostRepository;
    }

    public function enquireFreightCost(FreightCostRequest $filter)
    {
        return $this->courierCostRepository->getCourierCost($filter->input('country'), $filter->input('state'), $filter->input('weight'));
    }
}
