<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsCourierOrderLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
         Schema::create('iwms_courier_order_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('batch_id')->nullable();
            $table->string('wms_platform',60);
            $table->string('merchant_id',60);
            $table->string('sub_merchant_id',60);
            $table->string('reference_no');
            $table->string('wms_order_code',60)->nullable();
            $table->string('iwms_warehouse_code',60)->nullable();
            $table->string('marketplace_platform_id',60);
            $table->integer('iwms_courier_code');
            $table->string('platform_order_id',60);
            $table->string('tracking_no',60)->nullable();
            $table->string('store_name',60)->nullable();
            $table->longText('request_log',255);
            $table->longText('response_log',255)->nullable();
            $table->string('response_message',255)->nullable();
            $table->integer('status')->default("0")->comment('0 - Unverified; 1 - Success;2 - Failed');
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
        Schema::drop('iwms_courier_order_logs');
    }
}
