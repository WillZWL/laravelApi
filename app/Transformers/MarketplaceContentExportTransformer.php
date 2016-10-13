<?php

namespace App\Transformers;

use App\Models\MarketplaceContentExport;
use League\Fractal\TransformerAbstract;

class MarketplaceContentExportTransformer extends TransformerAbstract
{
    public function transform(MarketplaceContentExport $marketplaceContentExport)
    {
        return [
            'marketplace' => $marketplaceContentExport->marketplace,
            'field_value' => $marketplaceContentExport->field_value,
            'sort' => $marketplaceContentExport->sort,
        ];
    }
}
