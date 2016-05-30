<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMarketplaceProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->renameColumn('inventory', 'quantity');
            $table->renameColumn('fulfillment', 'fulfillment_method');
            $table->unsignedInteger('fulfillment_latency')->default(10);
            $table->string('condition', 21);
            $table->string('condition_note');
            $table->string('brand_in_platform');
            $table->decimal('profit', 10, 2)->nullable();
            $table->decimal('margin', 10, 2)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_latency', 'condition', 'condition_note', 'brand_in_platform', 'profit', 'margin']);
            $table->renameColumn('quantity', 'inventory');
            $table->renameColumn('fulfillment_method', 'fulfillment');
        });
    }
}
