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
	**  function getPlatformId
	**  this will return Store Name
	********************************************/

	public function submitProductPrice();
	/******************************************
	**  function getPlatformId
	**  this will return Store Name
	********************************************/

	public function submitProductInventory();
	/******************************************
	**  function getPlatformId
	**  this will return Store Name
	********************************************/
	
	public function submitProductCreate();
	/******************************************
	**  function getPlatformId
	**  this will return Store Name
	********************************************/
}