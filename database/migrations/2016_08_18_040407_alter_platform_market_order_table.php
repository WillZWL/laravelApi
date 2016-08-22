<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPlatformMarketOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('platform_market_order', function (Blueprint $table) {
            $table->renameColumn('number_of_items_unshippped', 'number_of_items_unshipped');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->renameColumn('number_of_items_unshipped', 'number_of_items_unshippped');
    }
}
