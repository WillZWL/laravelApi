<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMerchantTypeToDeliveyTypeMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('delivery_type_mappings', function (Blueprint $table) {
            $table->string('merchant_type')->after('courier_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_type_mappings', function (Blueprint $table) {
            $table->dropColumn('merchant_type');
        });
    }
}
