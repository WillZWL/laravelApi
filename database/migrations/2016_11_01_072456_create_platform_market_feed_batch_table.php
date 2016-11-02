<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformMarketFeedBatchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_market_feed_batch', function (Blueprint $table) {
            $table->increments('id');
            $table->string('fun_name');
            $table->string('feed_id');
            $table->string('update_id');
            $table->string('marketplace_sku');
            $table->string('process_status');
            $table->string('status');
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
        //
    }
}
