<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\OrderRetrieve::class,
        Commands\OrderTransfer::class,
        Commands\SubmitOrderFulfillmentFeed::class,
        Commands\SubmitProductFeed::class,
        Commands\SubmitPriceFeed::class,
        Commands\SubmitInventoryFeed::class,
        Commands\GetAmazonFeedResult::class,
        Commands\PlatformMarketOrderRetrieve::class,
        Commands\PlatformMarketReadyOrderTransfer::class,
        Commands\SubmitPlatformOrderFufillment::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('order:retrieve')
            ->dailyAt('0:50');

        $schedule->command('order:transfer')
            ->dailyAt('01:00');

        $schedule->command('feed:fulfillment')
            ->dailyAt('12:00');

        //$schedule->command('feed:product')
        //    ->everyTenMinutes();

        $schedule->command('feed:price')
            ->everyTenMinutes();

        $schedule->command('feed:inventory')
            ->everyTenMinutes();

        $schedule->command('feed:check')
            ->everyThirtyMinutes();
    }
}
