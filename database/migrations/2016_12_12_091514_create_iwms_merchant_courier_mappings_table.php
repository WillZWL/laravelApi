<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsMerchantCourierMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_merchant_courier_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->string("iwms_courier_code", 50);
            $table->string("merchant_id", 50);
            $table->string("merchant_courier_id", 20);
            $table->string("merchant_courier_name", 50)->nullable();
            $table->smallInteger('status')->default("1")->comment('0 - Inactive; 1 - Active;');
            $table->unique(['iwms_courier_code', 'merchant_id', 'merchant_courier_id'], 'idx_platform_merchant_courier');
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
        Schema::dropIfExists('iwms_merchant_courier_mappings');
    }
}
