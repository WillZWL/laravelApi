<?php 

namespace App\Contracts;

interface ApiPlatformInterface
{
	public function getPlatformId();
	/******************************************
	**  function getPlatformId
	**  this will return Store Name
	********************************************/

	public function retrieveOrder($storeName);
	/******************************************
	**  function retrieveOrder
	**  this will return get order and order items
	********************************************/

	public function getOrderList($storeName);
	/******************************************
	**  function getOrderList
	**  this will return order list
	********************************************/

	public function getOrderItemList($storeName,$orderId);
	/******************************************
	**  function getOrderItemList
	**  this will return order items
	********************************************/

}