<?php

namespace App\Repository;

use Illuminate\Http\Request;
use App\Models\PlatformMarketOrder;

class PlatformMarketOrderRepository
{
    public function getOrdersByStore(Request $request)
    {
        $query = PlatformMarketOrder::with('platformMarketOrderItem')
            ->join('user_stores', 'user_stores.market_store_id', '=', 'platform_market_order.market_store_id', 'inner')
            ->where('user_stores.user_id', '=', \Authorizer::getResourceOwnerId());

        switch ($request->get('status')) {
            case 'new':
                $query = $query->where('platform_market_order.esg_order_status', '<', 5);
                break;

            case 'ready':
                $query = $query->where('platform_market_order.esg_order_status', '=', 5);
                break;

            case 'shipped':
                $query = $query->where('platform_market_order.esg_order_status', '=', 6);
                break;
        }

        return $query->get();
    }
}
