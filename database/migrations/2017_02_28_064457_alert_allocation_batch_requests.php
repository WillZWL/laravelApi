<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertAllocationBatchRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('allocation_batch_requests', function (Blueprint $table) {
            $table->longText('response_log')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('allocation_batch_requests', function (Blueprint $table) {
            $table->string('response_log', 255)->nullable()->change();
        });
    }
}
