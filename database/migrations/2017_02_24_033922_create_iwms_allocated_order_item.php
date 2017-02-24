<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsAllocatedOrderItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_allocated_order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedinteger("iwms_allocated_order_id");
            $table->string("reference_no", 64);
            $table->string("line_no", 64);
            $table->string("sku", 32);
            $table->integer("quantity");
            $table->integer("allocated_qty");
            $table->foreign('iwms_allocated_order_id')->references('id')
                ->on('iwms_allocated_orders')
                ->onDelete('restrict')
                ->onUpdate('cascade');
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
         Schema::dropIfExists('iwms_allocated_order_items');
    }
}
