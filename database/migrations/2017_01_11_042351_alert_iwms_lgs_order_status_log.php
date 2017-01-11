<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertIwmsLgsOrderStatusLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('iwms_lgs_order_status_logs', function (Blueprint $table) {
            $table->string("tracking_no", 256)->after('platform_order_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('iwms_lgs_order_status_logs', function (Blueprint $table) {
            $table->dropColumn('tracking_no');
        });
    }
}
