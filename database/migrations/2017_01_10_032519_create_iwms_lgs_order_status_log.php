<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsLgsOrderStatusLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('iwms_lgs_order_status_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string("iwms_platform", 50);
            $table->string("esg_courier_id", 32);
            $table->string("so_no", 20);
            $table->string("platform_order_no", 256);
            $table->integer("status")->default(0);
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
        Schema::dropIfExists('iwms_lgs_order_status_logs');
    }
}
