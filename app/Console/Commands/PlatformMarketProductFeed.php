<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Config;

class PlatformMarketProductFeed extends BaseApiPlatformCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //action = updatePrice or updateInventory
    protected $signature = 'platformMarket:product {action} {--api= : amazon or lazada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
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
        $this->platfromMakert = array("lazada");
        $this->runPlatformMarketConsoleFunction();
    }

    public function runApiPlatformServiceFunction($stores, $apiName)
    {
        if ($stores){
            $action = $this->argument('action');
            foreach ($stores as $storeName => $store) {
                if($action = "updatePrice"){
                    $this->getApiPlatformProductFactoryService($apiName)->submitProductPrice($storeName);
                }else if($action = "updateInventory"){
                    $this->getApiPlatformProductFactoryService($apiName)->submitProductInventory($storeName);
                }
            } 
        }
    }
}
