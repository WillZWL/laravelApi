<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRelationInPlatformMarketOrderItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platform_market_order_item', function (Blueprint $table) {
            $table->unsignedInteger('platform_market_order_id')->after('id');
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
            $table->dropColumn('platform_market_order_id');
        });
    }
}
