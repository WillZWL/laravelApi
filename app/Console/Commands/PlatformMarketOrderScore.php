<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SoPriorityScoreService;

class PlatformMarketOrderScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platformMarket:setOrderScore {--platform=AMAZON} {--merchant=3DOODLER} {--score=2500}';

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
    public function __construct(SoPriorityScoreService $soPriorityScoreService)
    {
        parent::__construct();
        $this->soPriorityScoreService = $soPriorityScoreService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('Set Order Priority Score at . '.\Carbon\Carbon::now());
        $options = $this->option();
        if (isset($options['score'])) {
            $this->soPriorityScoreService->setSoScore($options);
        }
    }
}
