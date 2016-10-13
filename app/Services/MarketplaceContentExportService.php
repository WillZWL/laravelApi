<?php

namespace App\Services;

use App\Models\MarketplaceContentField;
use App\Models\MarketplaceContentExport;

class MarketplaceContentExportService
{
    public function getMarketplaceContentExport($marketplace)
    {
        return MarketplaceContentExport::whereMarketplace($marketplace)->whereStatus(1)->orderBy('sort', 'ASC')->get();
    }

    public function setting($data)
    {
        $marketplaceContentExports = MarketplaceContentExport::whereMarketplace($data['marketplace'])->get();
        if ($marketplaceContentExports) {
            foreach ($marketplaceContentExports as $marketplaceContentExport) {
                $marketplaceContentExport->delete();
            }
        }

        if (isset($data['field_value']) && isset($data['marketplace'])) {
            foreach ($data['field_value'] as $sort => $value) {
                \Log::info($sort);
                MarketplaceContentExport::create(['marketplace'=> $data['marketplace'], 'field_value' => $value, 'sort'=>$sort, 'status'=>1]);
            }
        }

        $marketplaceContentExports =  MarketplaceContentExport::whereMarketplace($data['marketplace'])->whereStatus(1)->orderBy('sort', 'ASC')->get();
        if ($marketplaceContentExports) {
            return ['success' => true, 'marketplace_content_export' => $marketplaceContentExports, 'msg' => 'Save marketplace content export field success'];
        } else {
            return ['fialed' => true, 'msg' => 'Currently no marketplace content export field'];
        }
    }
}
