<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SalesOrderStatisticsAddSku extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('sales_order_statistics', function (Blueprint $table) {
            $table->float('to_usd_rate', 11, 6)->change();
            $table->string('esg_sku', 15)->after('so_no');
            $table->unsignedTinyInteger('qty')->after('line_no');
            $table->decimal('selling_price', 15, 2)->after('qty');
            $table->decimal('profit', 15, 2)->after('tuv_fee');
            $table->float('margin')->after('profit');
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
            $table->dropColumn('esg_sku');
            $table->dropColumn('qty');
            $table->dropColumn('selling_price');
            $table->dropColumn('profit');
            $table->dropColumn('margin');
        });
    }
}
