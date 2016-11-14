<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmazonProductSizeTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amazon_product_size_tiers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('marketplace_sku_mapping_id');
            $table->unsignedTinyInteger('product_size')
                ->comment('1/Small Standard-Size; 2/Large Standard-Size; 3/Small Oversize; 4/Medium Oversize; 5/Large Oversize; 6/Special Oversize');
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
        Schema::drop('amazon_product_size_tiers');
    }
}
