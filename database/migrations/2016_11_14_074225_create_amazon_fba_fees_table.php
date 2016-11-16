<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmazonFbaFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amazon_fba_fees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('marketplace_sku_mapping_id');
            $table->decimal('storage_fee');
            $table->decimal('order_handing_fee');
            $table->decimal('pick_and_pack_fee');
            $table->decimal('weight_handing_fee');
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
        Schema::drop('amazon_fba_fees');
    }
}
