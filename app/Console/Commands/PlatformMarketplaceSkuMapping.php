<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Services\ApiPlatformFactoryService;

use Carbon\Carbon;
use App\Models\Schedule;
use Config;

class PlatformMarketplaceSkuMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMaket:skuMapping  {--api= : amazon or lazada} ';

   /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve orders from platfrom market like(amazon,lazada,etc)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ApiPlatformFactoryService $apiPlatformFactoryService)
    {
        parent::__construct();
        $this->apiPlatformFactoryService=$apiPlatformFactoryService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        if($stores=$this->getStores()){
            foreach ($stores as $storeName => $store) {
                $result = $this->apiPlatformFactoryService->initMarketplaceSkuMapping($storeName,$store);
                //$this->apiPlatformFactoryService->updateOrCreateSellingPlatform($storeName,$store);
               // $this->apiPlatformFactoryService->updateOrCreatePlatformBizVar($storeName,$store);
            }
        }
    }

    public function getStores()
    {
        $apiName = $this->option('api');
        if($apiName=="lazada"){
            $stores = Config::get('lazada-mws.store');
        }
        return $stores;
    }
}
