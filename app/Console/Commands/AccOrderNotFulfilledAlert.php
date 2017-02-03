<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AccOrderNotFulfilledService;

class AccOrderNotFulfilledAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accNotFulfilled:Order';

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

    private $accOrderNotFulfilledService;

    public function __construct(AccOrderNotFulfilledService $accOrderNotFulfilledService)
    {
        $this->accOrderNotFulfilledService = $accOrderNotFulfilledService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->accOrderNotFulfilledService->sendAccOrderNotFulfilledAlert();
    }
}
