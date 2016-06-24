<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAcknowledgeToAmazonOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->tinyInteger('acknowledge')->default(0)->after('latest_delivery_date')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amazon_orders', function (Blueprint $table) {
            $table->dropColumn('acknowledge');
        });
    }
}
