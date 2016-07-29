<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PlatformMarketOrderTransfer;
use App\Services\ApiPlatformFactoryService;

use App\Models\Marketplace;
use App\Models\PlatformMarketOrder;
use App\Models\PlatformMarketOrderItem;
use App\Models\PlatformMarketShippingAddress;

class  PlatformMarketOrderManage extends Controller
{
    public function __construct(PlatformMarketOrderTransfer $platformMarketOrderTransfer,ApiPlatformFactoryService $apiPlatformFactoryService)
    {
        $this->platformMarketOrderTransfer=$platformMarketOrderTransfer;
        $this->apiPlatformFactoryService=$apiPlatformFactoryService;
    }

    public function index(Request $request)
    {
        $data=array();
        $apiPlatform=$request->input("api_platform");
    	if($apiPlatform =="amazon"){
            $data["orderList"]=PlatformMarketOrder::amazonUnshippedOrder()->with("platformMarketOrderItem")->paginate(30);
        }else if($apiPlatform=="lazada"){
            $data["orderList"]=PlatformMarketOrder::lazadaUnshippedOrder()->with("platformMarketOrderItem")->paginate(30);
        }
       
        $data["apiPlatform"]=$apiPlatform;
        $dispatchType =$request->input("dispatch_type");
        $functionArr=array(
            "c"=>"setStatusToCanceled",
            "s"=>"setStatusToReadyToShip",
            "r"=>"setStatusToShipped",
        );
        $storeArr=$request->input("platform");
        $orderItemIds=$request->input("check");
        if($orderItemIds && $functionArr[$dispatchType]){
           foreach($orderItemIds as $orderItemId){
                $result=$this->apiPlatformFactoryService->$functionArr[$dispatchType]($storeArr[$orderItemId],$orderItemId);
           } 
        }
        return response()->view('platform-manager.index', $data);
    }


    public function transferOrder(Request $request)
    {
        $orderIds=$request->input("check");
        if($orderIds){
            $this->platformMarketOrderTransfer->transferOrderById($orderIds);
            return redirect('platform-market/transfer-order');
        }
        $orderList=PlatformMarketOrder::readyOrder()->paginate(30);
        $data=array('orderList' => $orderList );
        //$data['marketplaces'] = Marketplace::whereStatus(1)->get();
        return response()->view('platform-manager.transfer-order', $data);   
    }
}
