<?php 
namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\Schedule;
use App\Models\PlatformOrderFeed;
use App\Models\So;
use App\Models\SoShipment;
use Carbon\Carbon;

use App\Models\PlatformMarketOrder;
use App\Models\MpControl;
use App\Models\MarketplaceSkuMapping;
use App\Models\SellingPlatform;
use App\Models\PlatformBizVar;
use Excel;

class ApiPlatformFactoryService
{
	private $_requestData;
	
	public function __construct(ApiPlatformInterface $apiPlatformInterface)
	{
		$this->apiPlatformInterface=$apiPlatformInterface;
	}

	public function retrieveOrder($storeName,Schedule $schedule)
	{	
		$this->apiPlatformInterface->setSchedule($schedule);//set base schedule
		return $this->apiPlatformInterface->retrieveOrder($storeName);
	}

	public function getOrder($storeName)
	{
		$orderId="62141";
		return $order=$this->apiPlatformInterface->getOrder($storeName,$orderId);
	}

	public function getOrderList($storeName,Schedule $schedule)
	{
		$this->apiPlatformInterface->setSchedule($schedule);//set base schedule
		return $this->apiPlatformInterface->getOrderList($storeName);
	}

	public function getOrderItemList($storeName)
	{
		$orderId="4274384";
		return $this->apiPlatformInterface->getOrderItemList($storeName,$orderId);
	}
	
	public function submitOrderFufillment($apiName)
	{
		$bizType=$this->apiPlatformInterface->getPlatformId($apiName);
		$platformOrderIdList=$this->getPlatformOrderIdList($bizType);
        $esgOrders=$this->getEsgOrders($platformOrderIdList);
		if($esgOrders){
			foreach ($esgOrders as $esgOrder) {
				$esgOrderShipment = SoShipment::where('sh_no', '=', $esgOrder->so_no."-01")->where('status', '=', '2')->first();
				if($esgOrderShipment){
					$response=$this->apiPlatformInterface->submitOrderFufillment($esgOrder,$esgOrderShipment,$platformOrderIdList);
					if($response){
						$this->markSplitOrderShipped($esgOrder);
						$this->markPlatformMarketOrderShipped($esgOrder);
						if($bizType=="Amazon"){
							$this->updateOrCreatePlatformOrderFeed($esgOrder,$platformOrderIdList,$response);
						}
					}
				}
			}
		}
	}

	public function setStatusToCanceled($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToCanceled($storeName,$orderItemId);
	}

	public function setStatusToReadyToShip($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToReadyToShip($storeName,$orderItemId);
	}

	public function setStatusToPackedByMarketplace($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToPackedByMarketplace($storeName,$orderItemId);
	}

	public function setStatusToShipped($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToShipped($storeName,$orderItemId);
	}

	public function setStatusToFailedDelivery($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToFailedDelivery($storeName,$orderItemId);
	}

	public function setStatusToDelivered($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToDelivered($storeName,$orderItemId);
	}

	public function getStoreSchedule($storeName)
	{
		$previousSchedule = Schedule::where('store_name', '=', $storeName)
                            ->where('status', '=', 'C')
                            ->orderBy('last_access_time', 'desc')
                            ->first();
        $currentSchedule = Schedule::create([
            'store_name' => $storeName,
            'status' => 'N',
            // MWS API requested: Must be no later than two minutes before the time that the request was submitted.
            'last_access_time' => Carbon::now()->subMinutes(2)
        ]); 
        if (!$previousSchedule) {
            $previousSchedule = $currentSchedule;
        }
        return $previousSchedule;
	}

	private function updateOrCreatePlatformOrderFeed($esgOrder,$platformOrderIdList,$response)
	{
		$platformOrderFeed = PlatformOrderFeed::firstOrNew(['platform_order_id' => $esgOrder->platform_order_id]);
		$platformOrderFeed->platform = $platformOrderIdList[$esgOrder->platform_order_id];
		$platformOrderFeed->feed_type = '_POST_ORDER_FULFILLMENT_DATA_';
		if($response){
			$platformOrderFeed->feed_submission_id = $response['FeedSubmissionId'];
		    $platformOrderFeed->submitted_date = $response['SubmittedDate'];
		    $platformOrderFeed->feed_processing_status = $response['FeedProcessingStatus'];
		}else{
		    $platformOrderFeed->feed_processing_status = '_SUBMITTED_FAILED';
		}
		$platformOrderFeed->save();
	}

	private function getPlatformOrderIdList($bizType)
	{
		switch ($bizType) {
			case 'amazon':
				$platformOrderList = PlatformMarketOrder::amazonUnshippedOrder()
	            ->leftJoin('platform_order_feeds', 'platform_market_order.platform_order_id', '=', 'platform_order_feeds.platform_order_id')
	            ->whereNull('platform_order_feeds.platform_order_id')
	            ->select('platform_market_order.*')
	            ->get();
				break;
			default:
				$platformOrderList = PlatformMarketOrder::unshippedOrder()->where('biz_type',"=",$bizType)->get();
				break;
		}
        $platformOrderIdList = $platformOrderList->pluck('platform', 'platform_order_no')->toArray();
        return $platformOrderIdList;
	}

	private function getEsgOrders($platformOrderIdList)
	{
		return $esgOrders = So::whereIn('platform_order_id', array_keys($platformOrderIdList))
	        ->where('platform_group_order', '=', '1')
	        ->where('status', '=', '6')
	        ->get();
	}

	private function markSplitOrderShipped($order)
	{
        $splitOrders = So::where('platform_order_id', '=', $order->platform_order_id)
            ->where('platform_split_order', '=', 1)->get();
        $splitOrders->map(function($splitOrder) use($order) {
            $splitOrder->dispatch_date = $order->dispatch_date;
            $splitOrder->status = 6;
            $splitOrder->save();
        });
    }

    private function markPlatformMarketOrderShipped($order)
    {
    	$order = PlatformMarketOrder::where('platform_order_id', '=', $order->platform_order_id)->get();
    	$order->esg_order_status = 6;
    	$order->save();
    }

    
}