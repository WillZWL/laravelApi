<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsMerchantWarehouseMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_merchant_warehouse_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->string("iwms_warehouse_code", 50);
            $table->string("merchant_id", 50);
            $table->string("merchant_warehouse_id", 50);
            $table->string("merchant_warehouse_name", 50)->nullable();
            $table->smallInteger('status')->default("1")->comment('0 - Inactive; 1 - Active;');
            $table->unique(['iwms_warehouse_code','merchant_id', 'merchant_warehouse_id'], 'idx_platform_merchant_country_warehouse');
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
        Schema::dropIfExists('iwms_merchant_warehouse_mappings');
    }
}
