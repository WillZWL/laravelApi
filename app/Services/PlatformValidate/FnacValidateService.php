<?php

namespace App\Services\PlatformValidate;

use App\Models\PlatformMarketOrder;
/**
*
*/
class FnacValidateService extends BaseValidateService
{

    private $order;
    function __construct(PlatformMarketOrder $order)
    {
        $this->order=$order;
        parent::__construct($order,$this->getPlatformAccountInfo($order),"FN");
    }

    /**
     * @param FnacOrder $order
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
        $countryCode = strtoupper(substr($order->platform, -2));
        switch ($platformAccount) {
            case 'BC':
                $platform["accountName"] = 'BrandsConnect';
                if ($countryCode == "ES") {
                    $platform["alertEmail"] = 'fnaces@brandsconnect.net';
                } else if ($countryCode == "PT") {
                    $platform["alertEmail"] = 'fnacpt@brandsconnect.net';
                }
                break;
        }
        return  $platform;
    }
}