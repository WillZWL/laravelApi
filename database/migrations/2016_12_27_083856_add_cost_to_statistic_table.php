<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCostToStatisticTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('sales_order_statistics', function (Blueprint $table) {
            $table->decimal('marketplace_fee', 15, 2)->change();
            $table->decimal('esg_fee', 15, 2)->change();
            $table->decimal('vat', 15, 2)->change();
            $table->decimal('duty', 15, 2)->change();
            $table->decimal('payment_gateway_fee', 15, 2)->change();
            $table->decimal('psp_admin_fee', 15, 2)->change();
            $table->decimal('shipping_cost', 15, 2)->change();

            $table->tinyInteger('line_no')->after('so_no');
            $table->decimal('supplier_cost', 15, 2)->after('line_no');
            $table->decimal('accessory_cost', 15, 2)->after('supplier_cost');
            $table->decimal('marketplace_list_fee', 15, 2)->after('accessory_cost');
            $table->decimal('marketplace_fixed_fee', 15, 2)->after('marketplace_list_fee');
            $table->decimal('marketplace_commission', 15, 2)->after('marketplace_fixed_fee');
            $table->decimal('warehouse_cost', 15, 2)->after('shipping_cost');
            $table->decimal('fulfilment_by_marketplace_fee', 15, 2)->after('warehouse_cost');
            $table->decimal('tuv_fee', 15, 2)->after('fulfilment_by_marketplace_fee');
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
            $table->dropColumn('line_no');
            $table->dropColumn('marketplace_list_fee');
            $table->dropColumn('marketplace_fixed_fee');
            $table->dropColumn('marketplace_commission');
            $table->dropColumn('supplier_cost');
            $table->dropColumn('accessory_cost');
            $table->dropColumn('warehouse_cost');
            $table->dropColumn('tuv_fee');
            $table->dropColumn('fulfilment_by_marketplace_fee');
        });
    }
}
