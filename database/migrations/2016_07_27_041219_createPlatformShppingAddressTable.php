<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformShppingAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_market_shipping_address', function (Blueprint $table) {
            $table->increments('id');
            $table->string('platform_order_id');
            $table->string('name');
            $table->string('address_line_1');
            $table->string('address_line_2');
            $table->string('address_line_3');
            $table->string('city');
            $table->string('county');
            $table->string('district');
            $table->string('state_or_region');
            $table->string('postal_code');
            $table->string('country_code');
            $table->string('phone');
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
         Schema::drop('platform_market_shipping_address');
    }
}
