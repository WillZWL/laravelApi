<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformMarketMappingAttributeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('platform_market_mapping_attribute', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attribute_type_id');
            $table->string('esg_attribute');
            $table->string('marketplace_attribute');
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
        Schema::drop('platform_market_mapping_attribute');
    }
}
