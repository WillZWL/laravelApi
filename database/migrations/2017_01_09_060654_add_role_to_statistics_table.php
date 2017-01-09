<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRoleToStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('sales_order_statistics', function (Blueprint $table) {
            $table->string('buyer', 20)->after('to_usd_rate');
            $table->string('operator', 20)->after('to_usd_rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_esg')->table('sales_order_statistics', function (Blueprint $table) {
            $table->dropColumn('buyer');
            $table->dropColumn('operator');
        });
    }
}
