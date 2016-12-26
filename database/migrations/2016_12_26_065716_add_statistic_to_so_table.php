<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatisticToSoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('so', function (Blueprint $table) {
            $table->unsignedTinyInteger('statistic')
                    ->nullable()
                    ->default(0)
                    ->after('acknowledge_on')
                    ->comment('relate to sales_order_statistics table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_esg')->table('so', function (Blueprint $table) {
            $table->dropColumn('statistic');
        });
    }
}
