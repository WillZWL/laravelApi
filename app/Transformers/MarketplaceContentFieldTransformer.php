<?php

namespace App\Transformers;

use App\Models\MarketplaceContentField;
use League\Fractal\TransformerAbstract;

class MarketplaceContentFieldTransformer extends TransformerAbstract
{
    public function transform(MarketplaceContentField $marketplaceContentField)
    {
        return [
            'marketplace_field_value' => $marketplaceContentField->value,
            'marketplace_field_name' => $marketplaceContentField->name,
        ];
    }
}
