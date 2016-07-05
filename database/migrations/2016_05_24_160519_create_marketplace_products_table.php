<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketplaceProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('marketplace_sku');
            $table->string('esg_sku');
            $table->bigInteger('mp_control_id')->unsigned();
            $table->string('ean', 13);
            $table->string('upc', 12);
            $table->string('asin', 10);
            $table->integer('mp_category_id');
            $table->integer('mp_sub_category_id');
            $table->decimal('price', 10, 2);
            $table->integer('inventory')->default(0);
            $table->string('fulfillment');
            $table->string('delivery_type');
            $table->char('listing_status', 1)->default('N');
            $table->tinyInteger('process_status')->default(0);
            $table->tinyInteger('status')->default(1);
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
        Schema::drop('marketplace_products');
    }
}
