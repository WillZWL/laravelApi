<?php

namespace App\Services\PlatformValidate;

use App\Models\PlatformMarketOrder;

class LazadaValidateService extends BaseValidateService
{
    private $order;
    public function __construct(PlatformMarketOrder $order)
    {
        $this->order = $order;
        parent::__construct($order, $this->getPlatformAccountInfo($order), 'LZ');
    }

    /**
     * @param AmazonOrder $order
     *
     * @return bool
     */
    public function validateOrder()
    {
        $alertEmail = 'it@eservicesgroup.net';
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
                $platform['alertEmail'] = 'lazadamy@brandsconnect.net';
                break;
            case 'CS':
                $platform['accountName'] = 'CambridgeSoundworks';
                $platform['alertEmail'] = 'cambridgesoundworks@aheaddigital.net';
                break;
            case 'ML':
                $platform['accountName'] = 'MattelLazada';
                $platform['alertEmail'] = 'mattel@apresslink.com';
                break;
            case 'PX':
                $platform['accountName'] = 'ProductXpress';
                $platform['alertEmail'] = 'lazada@eservicesgroup.com';
                break;
            case 'BM':
                $platform['accountName'] = 'Blue Microphones';
                $platform['alertEmail'] = 'bluemic@chatandvision.com';
                break;
        }

        return  $platform;
    }
}
