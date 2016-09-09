<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformMarketOrdertable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_market_order', function (Blueprint $table) {
            $table->increments('id');
            $table->string('platform');
            $table->string('biz_type');
            $table->string('platform_order_id');
            $table->dateTime('purchase_date');
            $table->dateTime('last_update_date');
            $table->string('order_status');
            $table->string('fulfillment_channel');
            $table->string('sales_channel');
            $table->string('ship_service_level');
            $table->integer('shipping_address_id');
            $table->decimal('total_amount', 15, 2);
            $table->char('currency', 3);
            $table->integer('number_of_items_shipped');
            $table->integer('number_of_items_unshippped');
            $table->string('payment_method');
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->dateTime('earliest_ship_date');
            $table->dateTime('latest_ship_date');
            $table->dateTime('earliest_delivery_date');
            $table->dateTime('latest_delivery_date');
            $table->tinyInteger('acknowledge')->default(0);
            $table->string('remarks');
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
        Schema::drop('platform_market_order');
    }
}
