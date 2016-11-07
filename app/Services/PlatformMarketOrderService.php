<?php

namespace App\Services;

use App\User;
use Illuminate\Http\Request;
use App\Repository\PlatformMarketOrderRepository;

class PlatformMarketOrderService
{
    private $orderRepository;
    use ApiPlatformTraitService;

    public function __construct(PlatformMarketOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getOrders(Request $request)
    {
        $stores = User::find(\Authorizer::getResourceOwnerId())->stores()
            ->pluck('store_id')->all();

        return $this->orderRepository->getOrdersByStore($request, $stores);
    }

    public function getOrderDetails($id)
    {
        $platformOrders = $this->orderRepository->getOrderDetails($id);
        foreach ($platformOrders as $key => $platformOrder) {
            $this->orderRepository->getMattelDcSkuMappingOrderItems($platformOrder);
        }
        return $platformOrders;
    }

    public function exportOrdersToExcel(Request $request)
    {
        $newOrderList = $this->getOrders($request);
        print_r($newOrderList);exit();
        $path = \Storage::disk('merchant')->getDriver()->getAdapter()->getPathPrefix()."/excel";
        $cellData[]=array(
            "Lazada SKU","Shipping Name","Shipping Address","Shipping Address2","Shipping Address3","Shipping Phone Number","Shipping City","Shipping Postcode","Shipping Country","Billing Name","Billing Address","Billing Address2","Billing Address3","Billing Phone Number","Billing City","Billing Postcode","Billing Country","Payment Method","Paid Price","Unit Price","Shipping Fee","Item Name","Shipping Provider","Shipping Provider Type","Tracking Code","Promised shipping time","Status","Reason",
        );
        foreach($newOrderList as $newOrder){
            $shippingAddress = $newOrder->platformMarketShippingAddress();
            foreach ($newOrder->platformMarketOrderItem as $platformMarketOrderItem) {
                $cellData[]=array(
                    "Lazada SKU" => $platformMarketOrderItem->seller_sku,
                    "Shipping Name" => $shippingAddress->name,
                    "Shipping Address" => $shippingAddress->address_line_1,
                    "Shipping Address2" => $shippingAddress->address_line_2,
                    "Shipping Address3" => $shippingAddress->address_line_3,
                    "Shipping Phone Number" => $shippingAddress->phone,
                    "Shipping City" => $shippingAddress->city,
                    "Shipping Postcode" => $shippingAddress->postal_code,
                    "Shipping Country" => $shippingAddress->county,
                    "Billing Name" => $shippingAddress->bill_name,
                    "Billing Address" => $shippingAddress->bill_address_line_1,
                    "Billing Address2" => $shippingAddress->bill_address_line_2,
                    "Billing Address3" => $shippingAddress->bill_address_line_3,
                    "Billing Phone Number" => $shippingAddress->bill_phone,
                    "Billing City" => $shippingAddress->bill_city,
                    "Billing Postcode" => $shippingAddress->bill_postal_code,
                    "Billing Country" => $shippingAddress->bill_country_code,
                    "Payment Method" => $newOrder->payment_method,
                    "Paid Price" => $newOrder->total_amount,
                    "Unit Price" => $platformMarketOrderItem->item_price,
                    "Shipping Fee" => $platformMarketOrderItem->shipping_price,
                    "Item Name" => $platformMarketOrderItem->title,
                    "Shipping Provider" => $platformMarketOrderItem->shipment_provider,
                    "Shipping Provider Type" => $shippingAddress->ship_service_level,
                    "Tracking Code" => $platformMarketOrderItem->tracking_code,
                    "Promised shipping time" => $platformMarketOrderItem->latest_ship_date,
                    "Status" => $platformMarketOrderItem->status,
                    "reason" => $platformMarketOrderItem->reason
                ); 
            }
        }
        $excelFile = $this->generateMultipleSheetsExcel("newOrderList",$cellDataArr,$path);
        return $excelFile["path"].$excelFile["fileNames"];
    }
}
