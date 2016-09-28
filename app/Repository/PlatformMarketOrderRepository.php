<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\PlatformMarketOrder;

class PlatformMarketOrderRepository
{
    public function getOrdersByStore(Request $request, Array $stores = [])
    {
        $query = PlatformMarketOrder::with('platformMarketOrderItem')
            ->whereIn('platform_market_order.store_id', $stores);

        switch ($request->get('status')) {
            case 'new':
                $query = $query->whereIn('platform_market_order.esg_order_status', [1, 2, 3, 4, 13, 14]);
                break;

            case 'ready':
                $query = $query->where('platform_market_order.esg_order_status', '=', 5);
                break;

            case 'shipped':
                $query = $query->where('platform_market_order.esg_order_status', '=', 6);
                break;
        }

        return $query->paginate(30);
    }
}
