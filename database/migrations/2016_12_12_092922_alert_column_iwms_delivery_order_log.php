<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertColumnIwmsDeliveryOrderLog extends Migration
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
            $table->renameColumn('warehouse_id',"iwms_warehouse_code");
            $table->renameColumn('courier_id',"iwms_courier_code");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("iwms_delivery_order_logs");
    }
}
