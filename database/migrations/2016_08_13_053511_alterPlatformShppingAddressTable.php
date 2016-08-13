<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPlatformShppingAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
         Schema::table('platform_market_shipping_address', function (Blueprint $table) {
            $table->string('bill_name')->nullable()->after('phone');
            $table->string('bill_address_line_1')->nullable()->after('bill_name');
            $table->string('bill_address_line_2')->nullable()->after('bill_address_line_1');
            $table->string('bill_address_line_3')->nullable()->after('bill_address_line_2');
            $table->string('bill_city')->nullable()->after('bill_address_line_3');
            $table->string('bill_county')->nullable()->after('bill_city');
            $table->string('bill_district')->nullable()->after('bill_county');
            $table->string('bill_state_or_region')->nullable()->after('bill_district');
            $table->string('bill_postal_code')->nullable()->after('bill_state_or_region');
            $table->string('bill_country_code')->nullable()->after('bill_postal_code');
            $table->string('bill_phone')->nullable()->after('bill_country_code');
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
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('bill_name');
            $table->dropColumn('bill_address_line_1');
            $table->dropColumn('bill_address_line_2');
            $table->dropColumn('bill_address_line_3');
            $table->dropColumn('bill_city');
            $table->dropColumn('bill_county');
            $table->dropColumn('bill_district');
            $table->dropColumn('bill_state_or_region');
            $table->dropColumn('bill_postal_code');
            $table->dropColumn('bill_country_code');
            $table->dropColumn('bill_phone');
        });
    }
}
