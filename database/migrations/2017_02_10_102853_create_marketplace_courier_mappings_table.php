<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketplaceCourierMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_courier_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('courier_id');
            $table->string('courier_code', 50);
            $table->string('marketplace', 50);
            $table->string('marketplace_courier_name', 50);
            $table->smallInteger('status')->default("1")->comment('0 - Inactive; 1 - Active;');
            $table->unique(['courier_id', 'marketplace', 'marketplace_courier_name'], 'idx_marketplace_courier_name');
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
        Schema::drop('marketplace_courier_mappings');
    }
}
