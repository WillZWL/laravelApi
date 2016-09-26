<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_stores', function (Blueprint $table) {
            $table->increments('id');
            $table->string('store_name');
            $table->string('store_code');
            $table->string('marketplace');
            $table->char('country', 2);
            $table->char('currency', 3);
            $table->text('credentials')->comment('store account credentials');
            $table->tinyInteger('status')->default(1)->comment('0 - suspended; 1 - normal');
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
        Schema::drop('market_stores');
    }
}
