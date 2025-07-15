<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotesToPurchaseOrdersAndItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total_tax');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}
