<?php

namespace App\Http\Controllers\Api;

use App\Models\MarketplaceSkuMapping;
use App\Services\ShippingService;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class DeliveryController extends Controller
{
    private $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    public function getDeliveryOptionForSku($id)
    {
        $shippingOptions = $this->shippingService->shippingOptions($id);

        return response()->json($shippingOptions);
    }

    public function getOrderDeiveryCostBySoNo($soNo)
    {
        $orderDeliveryCost = $this->shippingService->orderDeliveryCost($soNo);

        return response()->json($orderDeliveryCost);
    }

    public function getOrderDeliveryOptionsBySoNo($soNo)
    {
        $orderDeliveryOptions = $this->shippingService->orderDeliveryOptions($soNo);

        return response()->json($orderDeliveryOptions);
    }

}
