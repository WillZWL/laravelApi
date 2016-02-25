<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmazonOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amazon_order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('asin');
            $table->string('seller_sku');
            $table->string('order_item_id');
            $table->string('title');
            $table->integer('quantity_ordered');
            $table->integer('quantity_shipped');
            $table->decimal('item_price', 15, 2);
            $table->decimal('shipping_price', 15, 2);
            $table->decimal('gift_wrap_price', 15, 2);
            $table->decimal('item_tax', 15, 2);
            $table->decimal('shipping_tax', 15, 2);
            $table->decimal('gift_wrap_tax', 15, 2);
            $table->decimal('shipping_discount', 15, 2);
            $table->decimal('promotion_discount', 15, 2);
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
        Schema::drop('amazon_order_items');
    }
}
