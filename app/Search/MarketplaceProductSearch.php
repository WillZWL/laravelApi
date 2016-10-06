<?php

namespace App\Search;

use App\Http\Requests\MarketplaceProductSearchRequest;
use App\Models\MarketplaceSkuMapping;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceProductSearch
{
    public static function apply(MarketplaceProductSearchRequest $filters)
    {
        $query = static::applyDecoratorsFromRequest($filters, (new MarketplaceSkuMapping())->newQuery());

        return $query;
    }

    private static function applyDecoratorsFromRequest(MarketplaceProductSearchRequest $request, Builder $query)
    {
        foreach ($request->all() as $filterName => $value) {
            if ($value !== '') {
                if ($filterName != "warehouse_id") {
                    $decorator = static::createFilterDecorator($filterName);
                    if (static::isValidDecorator($decorator)) {
                        if ($filterName == "inventory") {
                            $value = ["inventory" => $value, "warehouseId" => $request->input("warehouse_id")];
                        }
                        $query = $decorator::apply($query, $value);
                    }
                }
            }
        }
//var_dump($query->toSql());
        return static::getResults($query)->appends($request->input());
    }

    private static function createFilterDecorator($name)
    {
        return __NAMESPACE__ . '\\Filters\\' . studly_case($name);
    }

    private static function isValidDecorator($decorator)
    {
        return class_exists($decorator);
    }

    private static function getResults(Builder $query)
    {
        return $query->paginate(30);
    }
}
