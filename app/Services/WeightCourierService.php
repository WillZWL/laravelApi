<?php

namespace App\Services;

use App\Repository\WeightCourierRepository;

class WeightCourierService
{
    private $weightCourierRepository;

    public function __construct(WeightCourierRepository $weightCourierRepository)
    {
        $this->weightCourierRepository = $weightCourierRepository;
    }

    public function all()
    {
        return $this->weightCourierRepository->all();
    }
}
