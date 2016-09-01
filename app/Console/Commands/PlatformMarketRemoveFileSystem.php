<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiBaseService;

class PlatformMarketRemoveFileSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:removeApiFileSystem';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all api storage file before 30 days';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ApiBaseService $apiBaseService)
    {
        parent::__construct();
        $this->apiBaseService = $apiBaseService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
        $this->apiBaseService->removeApiFileSystem(date("Y-m-d",strtotime("-30 days")));
    }
}
