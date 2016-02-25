<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmazonShippingAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amazon_shipping_addresses', function (Blueprint $table) {
            $table->increments('id');
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
        Schema::drop('amazon_shipping_addresses');
    }
}
