<?php

namespace App\Services\PlatformValidate;

use App\Models\PlatformMarketOrder;

class AmazonValidateService extends BaseValidateService
{
    private $order;
    public function __construct(PlatformMarketOrder $order)
    {
        $this->order = $order;
        parent::__construct($order, $this->getPlatformAccountInfo($order), 'AZ');
    }

    /**
     * @param AmazonOrder $order
     *
     * @return bool
     */
    public function validateOrder()
    {
        $alertEmail = 'it@eservicesgroup.net';
        //$alertEmail = 'handy.hon@eservicesgroup.com';
        $valid = parent::validate();

        return $valid == '1' ? true : false;
    }

    public function getPlatformAccountInfo($order)
    {
        $platform = '';
        $platformAccount = strtoupper(substr($order->platform, 0, 2));
        switch ($platformAccount) {
            case 'BC':
                $platform['accountName'] = 'BrandsConnect';
                $platform['alertEmail'] = 'amazon_us@brandsconnect.net';
                break;
            case 'PX':
                $platform['accountName'] = 'ProductXpress';
                $platform['alertEmail'] = 'amazoneu@productxpress.com';
                break;
            case 'CV':
                $platform['accountName'] = 'ChatAndVision';
                $platform['alertEmail'] = 'amazonus-group@chatandvision.com';
                break;
        }

        return  $platform;
    }
}
