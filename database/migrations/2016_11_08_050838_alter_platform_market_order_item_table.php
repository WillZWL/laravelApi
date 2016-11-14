<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPlatformMarketOrderItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platform_market_order_item', function (Blueprint $table) {
            $table->string('marketplace_sku')->after('seller_sku');
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
            $table->dropColumn('marketplace_sku');
        });
    }
}
