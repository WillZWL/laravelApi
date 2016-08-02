<?php 

namespace App\Services;

use App\Contracts\ApiPlatformInterface;
use App\Models\Schedule;

//use lazada api package
use App\Repository\LazadaMws\LazadaProductList;

class ApiLazadaProductService extends ApiBaseService  implements ApiPlatformInterface 
{
	private $storeCurrency;
	function __construct()
	{

	}

	public function retrieveOrder($storeName){

	}
	public function getOrderList($storeName){

	}
	public function getOrderItemList($storeName, $orderId){

	}
	public function getPlatformId()
	{
		return "Lazada";
	}

	public function getProductList($storeName)
	{	
		$this->lazadaProductList=new LazadaProductList($storeName);
		$this->storeCurrency=$this->lazadaProductList->getStoreCurrency();
		//$dateTime=date(\DateTime::ISO8601, strtotime($this->getSchedule()->last_access_time));
		$dateTime=date(\DateTime::ISO8601, strtotime("2016-07-31"));
		$this->lazadaProductList->setCreatedBefore($dateTime);
		$orginProductList=$this->lazadaProductList->fetchProductList();
		$this->saveDataToFile(serialize($orginProductList),"getProductList");
        return $orginProductList;
	}

	public function updateProductPrice($storeName)
	{
		$this->lazadaProductUpdate=new LazadaProductUpdate($storeName);
		$this->storeCurrency=$this->lazadaProductUpdate->getStoreCurrency();
		$result=$this->lazadaProductUpdate->submitXmlData($xmlData);
		$this->saveDataToFile(serialize($result),"getProductList");
        return $result;
	}


}