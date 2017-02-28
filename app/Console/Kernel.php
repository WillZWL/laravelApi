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
        Commands\PlatformMarketOrderFufillment::class,
        Commands\PlatformMarketUpdatePendingStatus::class,
        Commands\PlatformMarketProductFeed::class,
        Commands\PlatformMarketRemoveFileSystem::class,
        Commands\PlatformMarketProductReport::class,
        Commands\PlatformMarketOrderReport::class,
        Commands\PlatformMarketFeedResult::class,
        Commands\PlatformMarketMattelProductFeed::class,
        Commands\PlatformMarketReasons::class,
        Commands\PlatformMarketUpdateOrderItemSellerSku::class,
        Commands\PlatformMarketLowStockAlert::class,
        Commands\PlatformMarketOrderScore::class,
        Commands\LazadaSGOrderRetrieve::class,
        Commands\PlatformMarketOrderUpdate::class,
        Commands\IwmsDeliveryOrder::class,
        Commands\IwmsCourierOrder::class,
        Commands\IwmsFulfillmentOrder::class,
        Commands\LgsOrderReadyToship::class,
        Commands\AccOrderNotFulfilledAlert::class,
        Commands\SkuCreatedAlert::class,
        Commands\SkuListingAlert::class,
        Commands\OrderPackList::class,
        Commands\IwmsLgsOrder::class
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

        $schedule->command('lazadaSg:orderRetrieve')
                ->cron('45 23,00-10 * * * *');

        $schedule->command('platformMarket:orderRetrieve',array('--api' => 'hourlyAll'))
            ->cron('50 23,00-10 * * * *');
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

        $schedule->command('platformMarket:updateSellerSku',array('--api' => 'all'))
             ->dailyAt('23:55');
        $schedule->command('platformMarket:updateSellerSku',array('--api' => 'all'))
             ->dailyAt('01:55');
        $schedule->command('platformMarket:updateSellerSku',array('--api' => 'all'))
             ->dailyAt('08:55');

        $schedule->command('platformMarket:orderTransfer')
            ->cron('00 00-10 * * * *');

        $schedule->command('feed:fulfillment')
            ->dailyAt('12:00');

        $schedule->command('platformMarket:updateShipment', array('--api' => 'all'))
            ->dailyAt('12:20');
        $schedule->command('platformMarket:orderUpdate', array('--api' => 'all'))
            ->dailyAt('12:20');

        $schedule->command('platformMarket:orderReport alertOrder', array('--api' => 'lazada'))
            ->dailyAt('01:00');

        $schedule->command('platformMarket:productReport getInventory', array('--api' => 'amazon'))
            ->dailyAt('00:00');
        $schedule->command('platformMarket:productReport getReport', array('--api' => 'amazon'))
            ->dailyAt('00:30');

        //$schedule->command('feed:product')
        //    ->everyTenMinutes();

        $schedule->command('platformMarket:product updatePriceInventory', array('--api' => 'all'))
            ->everyThirtyMinutes();
        $schedule->command('Mattel:product updateInventory')->hourly();
        $schedule->command('platformMarket:feedBack', array('--api' => 'all'))->hourly();

        $schedule->command('feed:price')
            ->everyThirtyMinutes();

        $schedule->command('feed:inventory')
            ->everyThirtyMinutes();

        $schedule->command('feed:check')
            ->everyThirtyMinutes();

        $schedule->command('platformMarket:removeApiFileSystem')
            ->monthlyOn(4, '15:00');

        $schedule->command('platformMarket:sendLowStockAlert')
            ->dailyAt('16:00');

        $schedule->command('platformMarket:setOrderScore',
                    array('--platform' => 'AMAZON', '--merchant' => '3DOODLER', '--score' => '2500'))
            ->dailyAt('00:30');
        $schedule->command('platformMarket:setOrderScore',
                    array('--platform' => 'AMAZON', '--merchant' => '3DOODLER', '--score' => '2500'))
            ->dailyAt('02:30');
        $schedule->command('platformMarket:setOrderScore',
                    array('--platform' => 'AMAZON', '--merchant' => '3DOODLER', '--score' => '2500'))
            ->dailyAt('09:30');
        $schedule->command('platformMarket:setOrderScore',
                    array('--platform' => 'DISPATCH', '--merchant' => 'RING', '--score' => '2000'))
            ->dailyAt('00:30');
        $schedule->command('platformMarket:setOrderScore',
                    array('--platform' => 'DISPATCH', '--merchant' => 'RING', '--score' => '2000'))
            ->dailyAt('02:30');
        $schedule->command('platformMarket:setOrderScore',
                    array('--platform' => 'DISPATCH', '--merchant' => 'RING', '--score' => '2000'))
            ->dailyAt('09:30');

        $schedule->command('accNotFulfilled:Order')
            ->dailyAt('02:30');
        $schedule->command('accNotFulfilled:Order')
            ->dailyAt('09:30');

        $schedule->command('Iwms:deliveryOrder create',array('--wms' => '4px'))
            ->everyThirtyMinutes();

        $schedule->command('sku:SendNewSkuAlert',
                    array('--sku_type' => '1'))
            ->dailyAt('01:00');

        $schedule->command('sku:SendSkuListingAlert',
                    array('--sku_type' => '1'))
            ->dailyAt('01:00');
    }
}
