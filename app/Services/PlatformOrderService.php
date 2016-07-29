<?php 

namespace App\Services;
//use SplEnum; 
/**
* 
*/
abstract class PlatformOrderService 
{
	const ORDER_STATUS_CANCEL = 0;
	const ORDER_STATUS_PAID = 2;
    const ORDER_STATUS_READYTOSHIP = 5;
    const ORDER_STATUS_SHIPPED = 6;
    const ORDER_STATUS_RETURENED = 7;
    const ORDER_STATUS_RETURENED_REJECTED = 8;
    const ORDER_STATUS_DELIVERED = 9;
    const ORDER_STATUS_COMPLETE = 10;
    const ORDER_STATUS_PENDING = 13;
    const ORDER_STATUS_UNSHIPPED = 14;
    const ORDER_STATUS_FAIL = 15;

	//0 = Inactive / 1 = New / 2 = Paid / 3 = Fulfilment AKA Credit Checked / 4 = Partial Allocated / 5 = Full Allocated / 6 = Shipped
	function __construct()
	{
		
	}

}


