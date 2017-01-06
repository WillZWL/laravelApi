<?php

namespace App\Repository;

use App\Http\Requests\SalesReportRequest;
use App\Models\So;

class SalesReportRepository
{
    public function getSalesOrderInfo(SalesReportRequest $filter)
    {
        $query = So::where('platform_group_order', '=', 1);
        $query = $query->whereBetween($filter->get('date_type'), [$filter->get('start_date'), $filter->get('end_date')]);

        if ($filter->get('marketplace_id')) {
            $query = $query->whereIn('platform_id', (array)$filter->get('marketplace_id'));
        }

        return $query->get();
    }
}