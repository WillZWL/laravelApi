<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryTypeMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_type_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('delivery_type')->comment('STD, EXPED, EXP, FBA etc...');
            $table->string('courier_type')->comment('courier_info.courier_type');
            $table->string('quotation_type')->comment('identify by battery type');
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
        Schema::drop('delivery_type_mappings');
    }
}
