<?php

namespace App\Services;

use App\Repository\WarehouseRepository;

class WarehouseService
{
    private $warehouseRepository;

    public function __construct(WarehouseRepository $warehouseRepository)
    {
        $this->warehouseRepository = $warehouseRepository;
    }

    public function all()
    {
        return $this->warehouseRepository->all();
    }

    public function defaultWarehouse()
    {
        return $this->warehouseRepository->defaultWarehouse();
    }
}
