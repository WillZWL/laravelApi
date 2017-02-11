<?php

namespace App\Transformers;

use App\Models\MarketplaceCourierMapping;
use League\Fractal\TransformerAbstract;

/**
*
*/
class MarketplaceCourierMappingTransformer extends TransformerAbstract
{
    public function transform(MarketplaceCourierMapping $marketplaceCourierMapping)
    {
        return [
            'id' => $marketplaceCourierMapping->id,
            'courier_id' => $marketplaceCourierMapping->courier_id,
            'courier_code' => $marketplaceCourierMapping->courier_code,
            'marketplace' => $marketplaceCourierMapping->marketplace,
            'marketplace_courier_name' => $marketplaceCourierMapping->marketplace_courier_name
        ];
    }

}
