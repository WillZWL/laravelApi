<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertBatchRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('batch_requests', function (Blueprint $table) {
            $table->string('wms_platform',64)->after('id');
            $table->string('merchant_id',64)->after('id');
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
            $table->dropColumn('wms_platform');
            $table->dropColumn('merchant_id');
        });
    }
}
