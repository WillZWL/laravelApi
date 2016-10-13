<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMattelSkuMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mattel_sku_mapping', function (Blueprint $table) {
            $table->increments('id');
            $table->string('warehouse_id');
            $table->string('mattel_sku');
            $table->string('dc_sku');
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
        Schema::drop('mattel_sku_mapping');
    }
}
