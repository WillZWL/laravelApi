<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketplaceContentExportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_content_export', function (Blueprint $table) {
            $table->increments('id');
            $table->string('marketplace');
            $table->string('field_value');
            $table->integer('sort')->default(0)->comment('Keep \'order by sort ASC\' for marketplace content export report');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('marketplace_content_export');
    }
}
