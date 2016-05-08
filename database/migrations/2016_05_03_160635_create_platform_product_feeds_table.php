<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformProductFeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_product_feeds', function (Blueprint $table) {
            $table->increments('id');
            $table->string('platform');
            $table->string('marketplace_sku');
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
        Schema::drop('platform_product_feeds');
    }
}
