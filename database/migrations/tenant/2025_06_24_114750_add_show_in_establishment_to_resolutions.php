<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShowInEstablishmentToResolutions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // co_type_documents
        Schema::table('co_type_documents', function (Blueprint $table) {
            $table->string('show_in_establishments')->default('all')->after('description');
            $table->json('establishment_ids')->nullable()->after('show_in_establishments');
        });

        // configuration_pos
        Schema::table('configuration_pos', function (Blueprint $table) {
            $table->string('show_in_establishments')->default('all')->after('cash_type');
            $table->json('establishment_ids')->nullable()->after('show_in_establishments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('establishment_to_resolutions', function (Blueprint $table) {
            Schema::table('co_type_documents', function (Blueprint $table) {
                $table->dropColumn(['show_in_establishments', 'establishment_ids']);
            });

            Schema::table('configuration_pos', function (Blueprint $table) {
                $table->dropColumn(['show_in_establishments', 'establishment_ids']);
            });
        });
    }
}
