<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMarketplaceContentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketplace_content_field', function (Blueprint $table) {
            $table->unique(['value'], 'idx_value');
        });
        Schema::table('marketplace_content_export', function (Blueprint $table) {
            $table->unique(['marketplace', 'field_value'], 'idx_marketplace_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketplace_content_field', function (Blueprint $table) {
            $table->dropUnique('idx_value');
        });
        Schema::table('marketplace_content_export', function (Blueprint $table) {
            $table->dropUnique('idx_marketplace_value');
        });
    }
}
