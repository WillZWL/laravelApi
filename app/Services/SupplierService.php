<?php

namespace App\Services;

use App\Repository\SupplierRepository;

class SupplierService
{
    private $supplierRepository;

    public function __construct(SupplierRepository $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    public function all()
    {
        return $this->supplierRepository->all();
    }
}
