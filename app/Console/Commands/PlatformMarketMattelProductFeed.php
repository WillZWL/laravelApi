<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiLazadaProductService;

class PlatformMarketMattelProductFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Mattel:product {action}';

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

    private $apiLazadaProductService;

    public function __construct(ApiLazadaProductService $apiLazadaProductService)
    {
        $this->apiLazadaProductService = $apiLazadaProductService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $action = $this->argument('action');
        if($action == "updateInventory"){
            $this->apiLazadaProductService->updatePlatformMarketMattleInventory();
        }else if($action == "getInventoryReport"){
            $storeNameArr = array("MDLAZADASG","MDLAZADATH","MDLAZADAPH","MDLAZADAID","MDLAZADAVN","MDLAZADAMY");
            foreach ($storeNameArr as $storeName) {
                $this->apiLazadaProductService->exportPlatformMarketInventoryAvailable($storeName);
            }
        }
    }
}
