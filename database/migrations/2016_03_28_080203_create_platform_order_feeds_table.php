<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformOrderFeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_order_feeds', function (Blueprint $table) {
            $table->increments('id');
            $table->string('platform');
            $table->string('platform_order_id');
            $table->string('feed_submission_id');
            $table->string('feed_type');
            $table->dateTime('submitted_date');
            $table->string('feed_processing_status');
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
        Schema::drop('platform_order_feeds');
    }
}
