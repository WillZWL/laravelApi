<?php

namespace App\Transformers;

use App\Models\Marketplace;
use League\Fractal\TransformerAbstract;

class MarketplaceTransformer extends TransformerAbstract
{
    public function transform(Marketplace $marketplace)
    {
        return [
            'marketplace_id' => $marketplace->id,
            'marketplace_name' => $marketplace->description,
        ];
    }
}
