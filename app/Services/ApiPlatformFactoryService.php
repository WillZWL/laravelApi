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
	
	public function submitOrderFufillment($bizType)
	{
		$platformOrderIdList=$this->getPlatformOrderIdList($bizType);
        $esgOrders=$this->getEsgOrders($platformOrderIdList);
		if($esgOrders){
			foreach ($esgOrders as $esgOrder) {
				$esgOrderShipment = SoShipment::where('sh_no', '=', $esgOrder->so_no."-01")->where('status', '=', '2')->first();
				if($esgOrderShipment){
					$response=$this->apiPlatformInterface->submitOrderFufillment($esgOrder,$esgOrderShipment,$platformOrderIdList);
					if($response){
						$this->markSplitOrderShipped($esgOrder);
						if($bizType=="amazon"){
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
			case 'lazada':
				$platformOrderList = PlatformMarketOrder::lazadaUnshippedOrder();
				break;
		}
        $platformOrderIdList = $platformOrderList->pluck('platform', 'platform_order_id')->toArray();
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

    //1 init Marketplace SKU Mapping 
    public function initMarketplaceSkuMapping($stores,$fileName="")
    {
    	$this->stores=$stores;
    	if($fileName){
			$filePath = 'storage/marketplace-sku-mapping/'.$fileName;
    	}else{
    		$filePath = 'storage/marketplace-sku-mapping/skus20160804.xlsx';	
    	}
		Excel::load($filePath, function($reader){
		    $sheetItem = $reader->all();
		    $mappingData=null;
		    foreach($sheetItem as $item){
		    	$itemData=$item->toArray();
		    	foreach ($this->stores as $storeName => $store) {
		   			$this->countryCode = strtoupper(substr($storeName, -2));
					$this->platformAccount = strtoupper(substr($storeName, 0, 2));
					$this->marketplaceId = strtoupper(substr($storeName, 0, -2));
					$this->store=$store;
					$this->mpControl=MpControl::where("marketplace_id",$this->marketplaceId)
									->where("country_id",'=',$this->countryCode)
									->where("status",'=','1')
									->first();	
					$insertActive=false;
					if($this->mpControl){
						if($itemData["country_id"]){
			    			if($itemData["country_id"]==$this->countryCode){
			    				$insertActive=true;
				    		}
					    }else{
					    	$insertActive=true;
					    }
					    if($insertActive){
					    	$mappingData=array(
							'marketplace_sku' =>$itemData["marketplace_sku"] ,
							'sku' => $itemData["esg_sku"],
							'mp_control_id'=>$this->mpControl->control_id,
							'marketplace_id' => $this->marketplaceId,
							'country_id' => $this->countryCode,
							'lang_id'=>'en',
							'currency'=>$this->store['currency']
							);
							//print_r($mappingData);
							$this->firstOrCreateMarketplaceSkuMapping($mappingData);
					    }
			        }
		        }
		    }
		});
    }
 	//2 init Marketplace SKU Mapping 
    public function updateOrCreateSellingPlatform($storeName,$store)
    {
    	$countryCode = strtoupper(substr($storeName, -2));
    	$platformAccount = strtoupper(substr($storeName, 0, 2));
    	$marketplaceId = strtoupper(substr($storeName, 0, -2));
    	$id="AC-".$platformAccount."LZ"."-GROUP".$countryCode;
    	$object=array();
		$object['id']=$id;
        $object['type'] ='ACCELERATOR';
        $object['marketplace'] =$marketplaceId;
        $object['merchant_id'] = 'ESG';
        $object['name'] = $store['name']." GROU ".$countryCode;
        $object['description'] = $store['name']." GROU ".$countryCode;
        $object['create_on'] = date("Y-m-d H:i:s");
        $sellingPlatform = SellingPlatform::updateOrCreate(
        	['id' => $id],$object
        );
        return $sellingPlatform;
    }
    //3 init Marketplace SKU Mapping 
    public function updateOrCreatePlatformBizVar($storeName,$store)
    {
    	$countryCode = strtoupper(substr($storeName, -2));
    	$platformAccount = strtoupper(substr($storeName, 0, 2));
    	$marketplaceId = strtoupper(substr($storeName, 0, -2));
    	$sellingPlatformId="AC-".$platformAccount."LZ"."-GROUP".$countryCode;
    	$object=array();
		$object['selling_platform_id']=$sellingPlatformId;
        $object['platform_country_id'] =$countryCode;
        $object['dest_country'] = $countryCode;
        $object['platform_currency_id'] =$store["currency"];
        $object['language_id'] = 'en';
        $object['delivery_type'] = 'EXP';
        $object['create_on'] = date("Y-m-d H:i:s");
        $platformBizVar = PlatformBizVar::updateOrCreate(
        	['selling_platform_id' => $sellingPlatformId],$object
        );
        return $platformBizVar;
    }
	//4 init Marketplace SKU Mapping 
    public function firstOrCreateMarketplaceSkuMapping($mappingData)
	{
		$object=array();
		$object['marketplace_sku']=$mappingData['marketplace_sku'];
        $object['sku'] = $mappingData['sku'];
        $object['mp_control_id'] =$mappingData['mp_control_id'];
        $object['marketplace_id'] = $mappingData['marketplace_id'];
        $object['country_id'] = $mappingData['country_id'];
        $object['lang_id'] = $mappingData['lang_id'];
        $object['condition'] = 'New';
        $object['delivery_type'] ='EXP';
        $object['currency'] =$mappingData['currency'];
        $object['status'] = 1;
        //$object['create_on'] = date("Y-m-d H:i:s");
        MarketplaceSkuMapping::firstOrCreate($object);
	}
}