<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_esg')->table('so_allocate', function (Blueprint $table) {
            $table->dropColumn('picklist_no');
        });

        Schema::connection('mysql_esg')->table('so', function (Blueprint $table) {
            $table->string("picklist_no", 15)->nullable()->after('statistic');
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
            $table->dropColumn('picklist_no');
        });

        Schema::connection('mysql_esg')->table('so_allocate', function (Blueprint $table) {
            $table->string("picklist_no", 15)->nullable()->after('sh_no');
        });
    }
}
