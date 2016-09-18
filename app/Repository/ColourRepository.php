<?php

namespace App\Repository;

use App\Models\Colour;

class ColourRepository
{
    public function all()
    {
        return Colour::whereStatus(1)->get();
    }
}