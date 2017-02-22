<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAllocationBatchRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('allocation_batch_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64);
            $table->longText("request_log")->nullable();
            $table->string('response_log', 255);
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
        Schema::drop('allocation_batch_requests');
    }
}
