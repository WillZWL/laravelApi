<?php

namespace App\Repository;

use App\Http\Requests\SalesReportRequest;
use App\Models\So;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Support\Facades\DB;

class SalesReportRepository
{
    public function getSalesOrderInfo(SalesReportRequest $filter)
    {
        $query = So::where('platform_group_order', '=', 1);
        $startDate = Date('Y-m-d', strtotime($filter->get('start_date')));
        $endDate = Date('Y-m-d', strtotime($filter->get('end_date')));
        $query = $query->whereBetween($filter->get('date_type'), [$startDate.' 00:00:00', $endDate.' 23:59:59']);

        if ($currency = $filter->get('currency')) {
            $query = $query->where('currency_id', $currency);
        }

        $platformIdPrefix = $platform_id = 'AC-';
        $accounts = array_filter(explode(',', $filter->get('selectedAccounts')));
        if (!empty($accounts)) {
            $platformIds = array_map(function ($acct) use ($platformIdPrefix) {
                return $platformIdPrefix.$acct;
            }, $accounts);
        } else {
            $platformIds = [
                $platformIdPrefix .= '%'
            ];
        }

        if ($marketplaceShortId = $filter->get('selectedMarketplace')) {
            $platformIds = array_map(function ($prefix) use ($marketplaceShortId) {
                return $prefix.$marketplaceShortId;
            }, $platformIds);
        }

        $countries = array_filter(explode(',', $filter->get('selectedCountries')));
        if (!empty($countries)) {
            $platformIds = array_map(function ($prefix) use ($countries) {
                return array_map(function ($country) use ($prefix) {
                    return $prefix.'-GROUP'.$country;
                }, $countries);
            }, $platformIds);
        } else {
            $platformIds = array_map(function ($prefix) {
                return $prefix.'-GROUP%';
            }, $platformIds);
        }

        $platformIdString = array_reduce($platformIds, function ($carry, $items) {
            if (is_array($items)) {
                return $carry. array_reduce($items, function ($carry, $i) {
                        return $carry.','.$i;
                });
            } else {
                return $carry.','.$items;
            }
        });

        $platformIds = array_filter(explode(',', $platformIdString));

        if (stripos($platformIdString, '%') === false) {
            $query->whereIn('platform_id', $platformIds);
        } else {
            $query = $query->where(function ($query) use ($platformIds) {
                array_reduce($platformIds, function ($q, $platform) use ($query) {
                    return $query = $query->orWhere('platform_id', 'LIKE', $platform);
                });
            });
        }

        $query->get();

        return $query->get();
    }
}