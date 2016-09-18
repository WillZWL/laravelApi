<?php

namespace App\Services;

use App\Repository\VersionRepository;

class VersionService
{
    private $versionRepository;

    public function __construct(VersionRepository $versionRepository)
    {
        $this->versionRepository = $versionRepository;
    }

    public function all()
    {
        return $this->versionRepository->all();
    }
}
