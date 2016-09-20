<?php

namespace App\Services;

use App\Repository\HscodeCategoryRepository;

class HscodeCategoryService
{
    private $hscodeCategoryRepository;

    public function __construct(HscodeCategoryRepository $hscodeCategoryRepository)
    {
        $this->hscodeCategoryRepository = $hscodeCategoryRepository;
    }

    public function all()
    {
        return $this->hscodeCategoryRepository->all();
    }
}
