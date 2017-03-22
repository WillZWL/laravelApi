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
            'marketplace_short_id' => $marketplace->short_id,
            'marketplace_name' => $marketplace->description,
            'marketplace_contact_name' => $marketplace->marketplace_contact_name,
            'marketplace_contact_phone' => $marketplace->marketplace_contact_phone,
            'marketplace_email_1' => $marketplace->marketplace_email_1,
            'marketplace_email_2' => $marketplace->marketplace_email_2,
            'marketplace_email_3' => $marketplace->marketplace_email_3
        ];
    }
}
