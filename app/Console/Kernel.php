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
        Commands\PlatformMarketplaceSkuMapping::class,
        Commands\SubmitPlatformOrderFufillment::class,
        Commands\PlatformMarketUpdatePendingStatus::class,
        Commands\PlatformMarketProductFeed::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('order:retrieve')
            ->dailyAt('23:50');
        $schedule->command('order:retrieve')
            ->dailyAt('01:50');
        $schedule->command('order:retrieve')
            ->dailyAt('08:50');

        $schedule->command('order:transfer')
            ->dailyAt('00:00');
        $schedule->command('order:transfer')
            ->dailyAt('02:00');
        $schedule->command('order:transfer')
            ->dailyAt('09:00');

        $schedule->command('platformMarket:orderRetrieve',array('--api' => 'fnac'))
            ->hourly();

        $schedule->command('platformMarket:orderRetrieve', array('--api' => 'all'))
            ->dailyAt('23:50');
        $schedule->command('platformMarket:orderRetrieve', array('--api' => 'all'))
            ->dailyAt('01:50');
        $schedule->command('platformMarket:orderRetrieve', array('--api' => 'all'))
            ->dailyAt('08:50');

        $schedule->command('platformMarket:updatePendingPayment',array('--api' => 'all'))
             ->dailyAt('23:50');
        $schedule->command('platformMarket:updatePendingPayment',array('--api' => 'all'))
             ->dailyAt('01:50');
        $schedule->command('platformMarket:updatePendingPayment',array('--api' => 'all'))
             ->dailyAt('08:50');

        $schedule->command('platformMarket:orderTransfer')
            ->dailyAt('00:00');
        $schedule->command('platformMarket:orderTransfer')
            ->dailyAt('02:00');
        $schedule->command('platformMarket:orderTransfer')
            ->dailyAt('09:00');

        $schedule->command('feed:fulfillment')
            ->dailyAt('12:00');
        $schedule->command('platformMarket:updateShipment', array('--api' => 'all'))
            ->dailyAt('12:20');

        //$schedule->command('feed:product')
        //    ->everyTenMinutes();

        $schedule->command('platformMarket:product updatePrice', array('--api' => 'all'))
            ->everyThirtyMinutes();

        $schedule->command('platformMarket:product updateInventory', array('--api' => 'all'))
            ->everyThirtyMinutes();

        $schedule->command('feed:price')
            ->everyThirtyMinutes();

        $schedule->command('feed:inventory')
            ->everyThirtyMinutes();

        $schedule->command('feed:check')
            ->everyThirtyMinutes();
    }
}
