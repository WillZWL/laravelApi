<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsAllocatedOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_allocated_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string("batch_id", 64);
            $table->string("wms_platform", 64);
            $table->string("merchant_id", 64);
            $table->string("sub_merchant_id", 64);
            $table->string("reference_no", 64);
            $table->string("picklist_no", 64);
            $table->integer("status")->default(0)->comment("1 => Success, -1 => Cancel");;
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
        Schema::dropIfExists('iwms_allocated_orders');
    }
}
