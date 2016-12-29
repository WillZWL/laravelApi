<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrderStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->create('sales_order_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->char('so_no', 8);
            $table->decimal('marketplace_fee');
            $table->char('marketplace_fee_currency', 3);
            $table->decimal('esg_fee');
            $table->char('esg_fee_currency', 3);
            $table->decimal('vat');
            $table->char('vat_currency');
            $table->decimal('duty');
            $table->char('duty_currency');
            $table->decimal('payment_gateway_fee');
            $table->char('payment_gateway_fee_currency');
            $table->decimal('psp_admin_fee');
            $table->char('psp_admin_fee_currency');
            $table->decimal('shipping_cost');
            $table->char('shipping_cost_currency');
            $table->decimal('to_usd_rate')->comment('sales order currency to USD rate');
            $table->timestamps();

            $table->index('so_no', 'so_no_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_esg')->drop('sales_order_statistics');
    }
}
