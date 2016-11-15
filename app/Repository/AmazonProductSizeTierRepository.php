<?php

namespace App\Repository;


use App\Models\AmazonProductSizeTier;

class AmazonProductSizeTierRepository
{
    public function find($id)
    {
        return AmazonProductSizeTier::find($id);
    }
}
