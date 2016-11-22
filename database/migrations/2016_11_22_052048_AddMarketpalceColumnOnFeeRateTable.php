<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMarketpalceColumnOnFeeRateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amazon_fulfilment_fee_rates', function (Blueprint $table) {
            $table->string('marketplace')->after('id');
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
            $table->dropColumn('marketplace');
        });
    }
}
