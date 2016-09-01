<?php

namespace App\Services;

use App\Repository\MerchantRepository;

class MerchantService
{
    private $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    public function all()
    {
        return $this->merchantRepository->all();
    }
}
