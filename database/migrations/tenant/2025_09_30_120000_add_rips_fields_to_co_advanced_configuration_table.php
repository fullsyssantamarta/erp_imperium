<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRipsFieldsToCoAdvancedConfigurationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_advanced_configuration', function (Blueprint $table) {
            $table->boolean('rips_enabled')->default(false);
            $table->unsignedBigInteger('rips_type_document_identification_id')->nullable();
            $table->string('rips_number_identification')->nullable();
            $table->string('rips_password')->nullable();
            $table->string('rips_url')->nullable();
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
            $table->dropColumn([
                'rips_enabled',
                'rips_type_document_identification_id',
                'rips_number_identification',
                'rips_password',
                'rips_url',
            ]);
        });
    }
}
