<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotesToPurchasesAndPurchaseItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('discount');
        });;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}
