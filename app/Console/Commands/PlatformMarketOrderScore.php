<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PlatformMarketOrderScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PlatformMarket:setOrderScore {--platform= : amazon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Order Priority Score For Platform Market Order';

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
    }
}
