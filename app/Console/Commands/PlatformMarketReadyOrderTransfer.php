<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlatformMarketOrderTransfer;

class PlatformMarketReadyOrderTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:orderTransfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert platform order to atomesg database';

    /**
     * Create a new command instance.
     */
    public function __construct(PlatformMarketOrderTransfer $platformMarketOrderTransfer)
    {
        parent::__construct();
        $this->platformMarketOrderTransfer = $platformMarketOrderTransfer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('Transfer orders at . '.\Carbon\Carbon::now());
        $this->platformMarketOrderTransfer->transferReadyOrder();
    }
}
