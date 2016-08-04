<?php 

namespace App\Services\PlatformValidate;

use App\Models\PlatformMarketOrder;
/**
* 
*/
class LazadaValidateService extends BaseValidateService 
{
	private $order;
	function __construct(PlatformMarketOrder $order)
	{
		$this->order=$order;
		parent::__construct($order,$this->getPlatformAccountInfo($order),"LZ");
	}

	/**
     * @param AmazonOrder $order
     * @return bool
     */
    public function validateOrder()
    {
        $alertEmail = 'it@eservicesgroup.net';
        $valid=parent::validate();
       	return $valid =="1" ? true : false;
    }


    public function getPlatformAccountInfo($order)
    {
    	$platform = '';
        $platformAccount = strtoupper(substr($order->platform, 0, 2));
        switch ($platformAccount) {
            case 'BC':
                $platform["accountName"] = 'BrandsConnect';
                $platform["alertEmail"] = 'lazadamy@brandsconnect.net';
                break;
            case 'VB':
                $platform["accountName"] = 'ValueBasket';
                $platform["alertEmail"] = 'lazada@valuebasket.com';
                break;
        }
     	return  $platform;
    }
}