<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeliveryTypeToStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('sales_order_statistics', function (Blueprint $table) {
            $table->string('delivery_type', 7)->after('psp_admin_fee');
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
            $table->dropColumn('delivery_type');
        });
    }
}
