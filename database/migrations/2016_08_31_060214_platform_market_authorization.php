<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PlatformMarketAuthorization extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platform_market_authorization', function (Blueprint $table) {
            $table->increments('id');
            $table->string('marketplace_id');
            $table->string('country_id');
            $table->string('client_id')->nullable();
            $table->string('authorization_code');
            $table->string('access_token');
            $table->string('refresh_token');
            $table->string('expire_date');
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
         Schema::drop('platform_market_authorization');
    }
}
