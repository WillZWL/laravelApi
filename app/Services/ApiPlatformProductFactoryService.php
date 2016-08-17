<?php 
namespace App\Services;

use App\Contracts\ApiPlatformProductInterface;
use App\Models\Schedule;

use Carbon\Carbon;


class ApiPlatformProductFactoryService
{
	private $_requestData;
	
	public function __construct(ApiPlatformProductInterface $apiPlatformProductInterface)
	{
		$this->apiPlatformProductInterface=$apiPlatformProductInterface;
	}

	public function getProductList($storeName)
	{
		return $this->apiPlatformProductInterface->getProductList($storeName);
	}

	public function submitProductPriceOrInventory($action)
	{	
		return $this->apiPlatformProductInterface->submitProductPriceOrInventory($action);
	}
}