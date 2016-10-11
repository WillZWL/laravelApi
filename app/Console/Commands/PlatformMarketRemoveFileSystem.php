<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiPlatformTraitService;

class PlatformMarketRemoveFileSystem extends Command
{
    use ApiPlatformTraitService;
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
        //print_r(date("Y-m-d",strtotime("-30 days"))); exit();
        $this->removeApiFileSystem(date("Y-m-d",strtotime("-30 days")));
    }
}
