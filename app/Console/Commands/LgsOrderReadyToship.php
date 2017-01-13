<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiLazadaService;

class LgsOrderReadyToship extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Lgs:Order {action}';

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
        //
        $this->apiLazadaService = new ApiLazadaService();
        $action = $this->argument('action');
        if($action == "readyToShip"){
            $this->apiLazadaService->cronSetEsgLgsOrderToReadyToShip();
        }else if($action == "getDocument"){
            $this->apiLazadaService->cornGetEsgLgsOrderDocument();
        }
    }
}
