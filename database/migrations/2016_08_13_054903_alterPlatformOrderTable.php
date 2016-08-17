<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPlatformOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('platform_market_order', function (Blueprint $table) {
            $table->string('platform_order_no')->after('platform_order_id');
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
        Schema::table('platform_market_order', function (Blueprint $table) {
            $table->dropColumn('platform_order_no');
        });
    }
}
