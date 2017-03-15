<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsProductLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_product_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('batch_id')->nullable();
            $table->string('wms_platform',60);
            $table->string('merchant_id',60);
            $table->string('sub_merchant_id',60);
            $table->string('sku');
            $table->string('category_id',60);
            $table->string('reference_code',60)->nullable();
            $table->string('upc',60);
            $table->string('battery_type',60)->nullable();
            $table->string('iwms_measure_uom',60)->nullable();
            $table->string('hscode',60);
            $table->string('declare_name',60);
            $table->string('declare_price',60)->nullable();
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
        Schema::dropIfExists('iwms_product_logs');
    }
}
