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
    protected $signature = 'Mattel:updateInventory';

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
         $this->apiLazadaProductService->updatePlatformMarketMattleInventory();
    }
}
