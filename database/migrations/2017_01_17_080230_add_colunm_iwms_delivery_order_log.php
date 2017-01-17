<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColunmIwmsDeliveryOrderLog extends Migration
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
            $table->string('wms_order_code')->nullable()->after('reference_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('iwms_delivery_order_logs', function (Blueprint $table) {
            $table->dropColumn('wms_order_code');
        });
    }
}
