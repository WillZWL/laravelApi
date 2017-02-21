<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIwmsAllocationPlanLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('iwms_allocation_plan_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('so_no', 32);
            $table->string('warehouse_id',60);
            $table->integer('iwms_request_id');
            $table->string('remarks',255)->nullable();
            $table->integer('status')->default("0")->comment('0 - Unverified; 1 - Success; 2 - Skipped;-1 - Failed');
            $table->timestamps();
            $table->index('so_no', 'idx_so_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('iwms_allocation_plan_logs');
    }
}
