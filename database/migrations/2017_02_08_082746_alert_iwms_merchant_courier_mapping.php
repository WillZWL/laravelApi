<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertIwmsMerchantCourierMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('iwms_merchant_courier_mappings', function (Blueprint $table) {
            $table->string('wms_platform', 64)->after('id');
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
        Schema::table('iwms_merchant_courier_mappings', function (Blueprint $table) {
            $table->dropColumn('wms_platform');
        });
    }
}
