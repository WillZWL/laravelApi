<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IwmsMerchantCategoryMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_merchant_category_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('iwms_category_id')->nullable();
            $table->string('merchant_id',60);
            $table->string('merchant_category_id',60);
            $table->string('merchant_category_name',60);
            $table->integer('status')->default("0")->comment('0 - Unverified; 1 - Success;2 - Failed');
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
        //
        Schema::dropIfExists('iwms_merchant_category_mappings');
    }
}
