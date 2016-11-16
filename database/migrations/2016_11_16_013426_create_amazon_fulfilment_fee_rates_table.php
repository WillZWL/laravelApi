<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmazonFulfilmentFeeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amazon_fulfilment_fee_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->char('country', 2);
            $table->unsignedTinyInteger('product_size');
            $table->decimal('max_weight_in_kg', 10, 4);
            $table->decimal('first_weight_in_kg', 10, 4);
            $table->decimal('first_fixed_fee')->comment('currency follow country');
            $table->decimal('addition_fee_per_kg')->comment('currency follow country');
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
        Schema::drop('amazon_fulfilment_fee_rates');
    }
}
