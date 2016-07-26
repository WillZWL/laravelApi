<?php 
namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\Schedule;
use Carbon\Carbon;

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

	public function getProductList($storeName)
	{
		return $this->apiPlatformInterface->getProductList($storeName);
	}

	public function setStatusToCanceled($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToCanceled($storeName,$orderItemId);
	}

	public function setStatusToPackedByMarketplace($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToPackedByMarketplace($storeName,$orderItemId);
	}

	public function setStatusToReadyToShip($storeName,$orderItemId)
	{
		return $this->apiPlatformInterface->setStatusToReadyToShip($storeName,$orderItemId);
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

}