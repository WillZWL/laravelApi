<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertIwmsOrderTableLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('iwms_courier_order_logs', function (Blueprint $table) {
            $table->string("battery",2)->default(0)->after('store_name');
            $table->string("pick_list_no", 15)->nullable()->after('response_message');
        });
        Schema::table('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->string("battery",2)->default(0)->after('store_name');
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
        Schema::table('iwms_courier_order_logs', function (Blueprint $table) {
            $table->dropColumn('pick_list_no');
            $table->dropColumn('battery');
        });
        Schema::table('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->dropColumn('battery');
        });
    }
}
