<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsFeedRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_feed_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('merchant_id', 64);
            $table->string("wms_platform", 64);
            $table->string("batch_request_id", 64);
            $table->string("iwms_request_id", 128);
            $table->text("responese_log")->nullable();
            $table->smallInteger("status")->default(0);
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
         Schema::dropIfExists('iwms_feed_requests');
    }
}
