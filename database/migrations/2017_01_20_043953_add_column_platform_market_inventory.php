<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnPlatformMarketInventory extends Migration
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
            $table->integer("failed_times")->default(0)->after('update_status');
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
            $table->dropColumn("failed_times");
        });
    }
}
