<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiscountCodeFieldsToCoAdvancedConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_advanced_configuration', function (Blueprint $table) {
            $table->boolean('validate_discount_code')->default(false)->after('validate_min_stock');
            $table->string('discount_code')->nullable()->after('validate_discount_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('co_advanced_configuration', function (Blueprint $table) {
            $table->dropColumn('validate_discount_code');
            $table->dropColumn('discount_code');
        });
    }
}
