<?php

namespace App\Console\Commands;

/*
*
*/
use Illuminate\Console\Command;
use App;
use Config;

class BaseApiPlatformCommand extends Command
{
    public $platfromMakert = array('lazada','priceminister');

    public function __construct()
    {
        parent::__construct();
    }

    public function getApiPlatformFactoryService($apiName)
    {
        return App::make('App\Services\ApiPlatformFactoryService', array('apiName' => $apiName));
    }

    //get stores function
    public function getStores($apiName)
    {
        $config = [
            'lazada' => Config::get('lazada-mws.store'),
            'amazon' => Config::get('amazon-mws.store'),
            'priceminister' => Config::get('priceminister-mws.store'),
            'tanga' => Config::get('tanga-mws.store'),
            'fnac' => Config::get('fnac-mws.store'),
            'wish' => Config::get('wish-mws.store'),
        ];
        $stores = $config[$apiName] ?: null;

        return $stores;
    }
}
