<?php

namespace App\Repository;

use App\Models\MarketplaceContentField;

class MarketplaceContentFieldRepository
{
    public function all()
    {
        return MarketplaceContentField::whereStatus(1)->get();
    }
}
