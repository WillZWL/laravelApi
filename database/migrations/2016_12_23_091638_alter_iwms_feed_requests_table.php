<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterIwmsFeedRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('iwms_feed_requests', function (Blueprint $table) {
            $table->renameColumn('responese_log', 'response_log');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('iwms_feed_requests', function (Blueprint $table) {
            $table->renameColumn('response_log', 'responese_log');
        });
    }
}
