<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('product', function (Blueprint $table) {
            $table->smallInteger('first_stocks_email')
               ->default("0")
               ->comment('0 - No; 1 - Yes;')
               ->after('reserved_qty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_esg')->table('product', function (Blueprint $table) {
            $table->dropColumn('first_stocks_email');
        });
    }
}
