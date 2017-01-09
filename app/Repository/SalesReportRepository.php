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

        if ($filter->get('marketplace_short_id')) {
            $platformIdPrefix = $platform_id = 'AC-'.$filter->get('marketplace_short_id').'-GROUP';
            if ($filter->get('country_id')) {
                $platform_id = $platformIdPrefix.$filter->get('country_id');
                $query = $query->where('platform_id', $platform_id);
            } else {
                $query = $query->where('platform_id', 'like', $platformIdPrefix."%");
            }
        }

        return $query->get();
    }
}