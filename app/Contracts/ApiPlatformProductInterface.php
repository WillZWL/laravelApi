<?php /**
* 
*/
namespace App\Contracts;

interface ApiPlatformProductInterface 
{
	
	public function getPlatformId();
	/******************************************
	**  function getPlatformId
	**  this will return Store Name
	********************************************/

	public function getProductList($storeName);
	/******************************************
	**  function getProductList
	**  this will return Marketplace product list
	********************************************/

	public function submitProductPrice();
	/******************************************
	**  function submitProductPrice
	**  this will change Marketplace product price
	********************************************/

	public function submitProductInventory();
	/******************************************
	**  function submitProductInventory
	**  this will change Marketplace product inventory
	********************************************/
	
	public function submitProductCreate();
	/******************************************
	**  function submitProductCreate
	**  this will create Marketplace Product
	********************************************/
}