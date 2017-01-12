<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIwmsDeliveryOrderLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
         Schema::table('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->string("sub_merchant_id", 64)->after('merchant_id');
            $table->string("tracking_no", 256)->nullable()->after('platform_order_id');
            $table->string("store_name", 256)->nullable()->after('tracking_no');
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
        Schema::table('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->dropColumn('sub_merchant_id');
            $table->dropColumn('tracking_no');
            $table->dropColumn('store_name');
        });
    }
}
