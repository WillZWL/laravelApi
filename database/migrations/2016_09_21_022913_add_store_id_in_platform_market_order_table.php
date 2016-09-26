<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStoreIdInPlatformMarketOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platform_market_order', function (Blueprint $table) {
            $table->unsignedInteger('market_store_id')->after('id');
            $table->unsignedInteger('platform_market_shipping_address_id')->after('market_store_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('platform_market_order', function (Blueprint $table) {
            $table->dropColumn('market_store_id');
            $table->dropColumn('platform_market_shipping_address_id');
        });
    }
}
