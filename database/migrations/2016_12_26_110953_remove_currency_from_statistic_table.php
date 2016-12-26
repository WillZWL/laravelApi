<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveCurrencyFromStatisticTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('sales_order_statistics', function (Blueprint $table) {
            $table->dropColumn('marketplace_fee_currency');
            $table->dropColumn('esg_fee_currency');
            $table->dropColumn('vat_currency');
            $table->dropColumn('duty_currency');
            $table->dropColumn('payment_gateway_fee_currency');
            $table->dropColumn('psp_admin_fee_currency');
            $table->dropColumn('shipping_cost_currency');
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
            $table->char('marketplace_fee_currency', 3)->after('marketplace_fee');
            $table->char('esg_fee_currency', 3)->after('esg_fee');
            $table->char('vat_currency', 3)->after('vat');
            $table->char('duty_currency', 3)->after('duty');
            $table->char('payment_gateway_fee_currency', 3)->after('payment_gateway_fee');
            $table->char('psp_admin_fee_currency', 3)->after('psp_admin_fee');
            $table->char('shipping_cost_currency', 3)->after('shipping_cost');
        });
    }
}
