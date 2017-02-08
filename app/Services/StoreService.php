<?php

namespace App\Services;

use App\Repository\StoreRepository;

class StoreService
{
    private $storeRepository;

    public function __construct(StoreRepository $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }

    public function all()
    {
        return $this->storeRepository->all();
    }
}
