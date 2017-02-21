<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertPlatformMarkertOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('platform_market_shipping_address', function (Blueprint $table) {
            $table->string('platform', 64)->after('platform_order_id');
        });
        Schema::table('platform_market_order_item', function (Blueprint $table) {
            $table->string('platform', 64)->after('platform_order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('platform_market_shipping_address', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
        Schema::table('platform_market_order_item', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
}
