<?php

namespace App\Repository;

use App\Models\Version;

class VersionRepository
{
    public function all()
    {
        return Version::whereStatus('A')->get();
    }
}