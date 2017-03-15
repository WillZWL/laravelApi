<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SettlementPreviewService;

class SettlementPreview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settlement:preview';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marketplace Settlement - Weekly Preview';

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
        $settlementPreviewService = new SettlementPreviewService();
        $settlementPreviewService->preview();
    }
}
