<?php

namespace App\Services;

use App\Repository\ColourRepository;

class ColourService
{
    private $colourRepository;

    public function __construct(ColourRepository $colourRepository)
    {
        $this->colourRepository = $colourRepository;
    }

    public function all()
    {
        return $this->colourRepository->all();
    }
}
