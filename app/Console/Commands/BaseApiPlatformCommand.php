<?php

namespace App\Console\Commands;

/*
*
*/
use Illuminate\Console\Command;
use App;
use Config;

abstract class BaseApiPlatformCommand extends Command
{
    public $platfromMakert = array('newegg', 'tanga', 'qoo10', 'priceminister');
    public $hourlyPlatfromMakert = array('lazada', 'fnac');
    abstract public function runApiPlatformServiceFunction($stores, $apiName);

    public function __construct()
    {
        parent::__construct();
    }

    public function getApiPlatformFactoryService($apiName)
    {
        return App::make('App\Services\ApiPlatformFactoryService', array('apiName' => $apiName));
    }

    public function getApiPlatformProductFactoryService($apiName)
    {
        return App::make('App\Services\ApiPlatformProductFactoryService', array('apiName' => $apiName));
    }

    public function runPlatformMarketConsoleFunction()
    {
        $apiOption = $this->option('api');
        if ($apiOption == 'all') {
            foreach ($this->platfromMakert as $apiName) {
                $this->runApiPlatformServiceFunction($this->getStores($apiName), $apiName);
            }
        } else if($apiOption == 'hourlyAll') {
            foreach ($this->hourlyPlatfromMakert as $apiName) {
                $this->runApiPlatformServiceFunction($this->getStores($apiName), $apiName);
            }
        } else {
            $this->runApiPlatformServiceFunction($this->getStores($apiOption), $apiOption);
        }
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
            'newegg' => Config::get('newegg-mws.store'),
            'qoo10' => Config::get('qoo10-mws.store'),
        ];
        $stores = $config[$apiName] ?: null;

        return $stores;
    }
}
