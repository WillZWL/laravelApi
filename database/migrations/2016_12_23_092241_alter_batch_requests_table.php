<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterBatchRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('batch_requests', function (Blueprint $table) {
            $table->string('response_log')->after('request_log');
            $table->string('iwms_request_id')->after('response_log');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('batch_requests', function (Blueprint $table) {
            $table->dropColumn('response_log');
            $table->dropColumn('iwms_request_id');
        });
    }
}
