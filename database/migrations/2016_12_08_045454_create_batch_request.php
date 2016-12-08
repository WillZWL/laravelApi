<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBatchRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('batch_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string("name", 64)->default("");
            $table->longText("request_log")->nullable();
            $table->string("remark", 512)->nullable();
            $table->dateTime("completion_time")->nullable();
            $table->string("status", 2)->default("N");
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
        Schema::dropIfExists('batch_requests');
    }
}
