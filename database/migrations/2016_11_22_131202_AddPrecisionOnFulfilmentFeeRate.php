<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrecisionOnFulfilmentFeeRate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amazon_fulfilment_fee_rates', function (Blueprint $table) {
            $table->decimal('addition_fee_per_kg', 15, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amazon_fulfilment_fee_rates', function (Blueprint $table) {
            $table->decimal('addition_fee_per_kg', 8, 2)->change();
        });
    }
}
