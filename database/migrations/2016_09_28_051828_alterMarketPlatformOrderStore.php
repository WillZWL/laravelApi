<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMarketPlatformOrderStore extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platform_market_order', function (Blueprint $table) {
            $table->renameColumn('market_store_id', 'store_id');
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
            $table->renameColumn('store_id', 'market_store_id');
        });
    }
}
