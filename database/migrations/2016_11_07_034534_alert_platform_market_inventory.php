<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertPlatformMarketInventory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platform_market_inventory', function (Blueprint $table) {
            $table->string('dc_sku')->after('mattel_sku');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('platform_market_inventory', function (Blueprint $table) {
            $table->dropColumn('dc_sku');
        });
    }
}
