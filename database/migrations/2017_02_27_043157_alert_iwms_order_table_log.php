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
        Schema::create('iwms_courier_order_logs', function (Blueprint $table) {
            $table->string("picklist_no", 15)->nullable()->after('response_message');
            $table->string("battery",2)->default("0")->after('store_name');
        });
        Schema::create('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->string("battery",2)->default("0")->after('store_name');
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
        Schema::create('iwms_courier_order_logs', function (Blueprint $table) {
            $table->dropColumn('picklist_no');
            $table->dropColumn('battery');
        });
        Schema::create('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->dropColumn('battery');
        });
    }
}
