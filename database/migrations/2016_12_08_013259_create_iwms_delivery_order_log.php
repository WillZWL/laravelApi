<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsDeliveryOrderLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 
        Schema::create('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('batch_id')->nullable();
            $table->string('so_no');
            $table->string('warehouse_id',60);
            $table->string('platform_id',60);
            $table->string('merchant_id',60);
            $table->integer('courier_id');
            $table->string('platform_order_no',60);
            $table->string('request_log',255);
            $table->string('response_log',255)->nullable();
            $table->string('response_message',255)->nullable();
            $table->integer('status')->default("0")->comment('0 - Unverified; 1 - Success;2 - Failed');
            $table->integer('repeat_request')->default("0")->comment('0 - No; 1 - Yes');
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
        Schema::drop('iwms_delivery_order_logs');
    }
}
