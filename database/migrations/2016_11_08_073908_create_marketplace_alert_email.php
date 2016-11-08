<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketplaceAlertEmail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_alert_email', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->comment('Email Type');
            $table->integer('store_id');
            $table->string('to_mail');
            $table->string('cc_mail');
            $table->string('bcc_mail');
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
        Schema::drop('marketplace_alert_email');
    }
}
