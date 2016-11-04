<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PlatformMarketOrderFufillment extends BaseApiPlatformCommand
{
    /**
      * The name and signature of the console command.
      *
      * @var string
      */
    protected $signature = 'platformMarket:updateShipment  {--api= : amazon or lazada}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the shipment status and tracking number into Platform Seller Central once the order has been shipped';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
        $this->platfromMakert = array("priceminister","newegg","fnac","tanga");
        $this->runPlatformMarketConsoleFunction();
    }

    public function runApiPlatformServiceFunction($stores, $apiName)
    {
        if ($stores){
            //print_r($this->getApiPlatformFactoryService($apiName));exit();
            $this->getApiPlatformFactoryService($apiName)->submitOrderFufillment($apiName);
        }
    }
}
