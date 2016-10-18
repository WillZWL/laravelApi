<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformMarketInventory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_market_inventory', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('store_id');
            $table->string('warehouse_id');
            $table->string('mattel_sku');
            $table->integer('inventory');
            $table->integer('threshold');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('platform_market_inventory');
    }
}
