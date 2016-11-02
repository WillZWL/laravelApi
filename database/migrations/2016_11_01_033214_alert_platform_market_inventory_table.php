<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertPlatformMarketInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('platform_market_inventory', function (Blueprint $table) {
            $table->string('update_status')->default("1")->comment('1 replace need update,0 replace do no update')->after('inventory');
            $table->string('marketplace_sku')->after('mattel_sku');
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
        Schema::table('platform_market_inventory', function (Blueprint $table) {
            $table->dropColumn('update_status');
            $table->dropColumn('marketplace_sku');
        });
    }
}
