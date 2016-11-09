<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePlatformMarketOrderItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platform_market_order_item', function (Blueprint $table) {
            $table->renameColumn('marketplace_sku', 'marketplace_item_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('platform_market_order_item', function (Blueprint $table) {
            $table->renameColumn('marketplace_item_code', 'marketplace_sku');
        });    }
}