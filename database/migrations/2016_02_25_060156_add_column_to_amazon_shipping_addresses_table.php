<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToAmazonShippingAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amazon_shipping_addresses', function (Blueprint $table) {
            $table->string('amazon_order_id')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amazon_shipping_addresses', function (Blueprint $table) {
            $table->dropColumn('amazon_order_id');
        });
    }
}
