<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEsgLgsOrderDocumentLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('esg_lgs_order_document_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string("store_name", 64);
            $table->string("order_item_ids", 256);
            $table->string("document_type", 64);
            $table->string("document_url", 256)->nullable();
            $table->integer("status")->default(0);
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
        //
        Schema::dropIfExists('esg_lgs_order_document_logs');
    }
}
