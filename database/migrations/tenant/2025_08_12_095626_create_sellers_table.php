<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('internal_code')->unique();
            $table->string('full_name');
            $table->unsignedBigInteger('type_document_identification_id');
            $table->string('document_number');
            $table->date('birth_date')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            // Datos laborales
            $table->date('start_date')->nullable();
            $table->enum('status', ['Activo', 'Inactivo'])->default('Activo');
            $table->string('role')->nullable();
            $table->string('area')->nullable();
            $table->string('shift')->nullable();
            // Control de ventas
            $table->decimal('monthly_goal', 15, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2)->nullable();
            $table->string('commission_type')->nullable();
            $table->timestamps();

            $table->foreign('type_document_identification_id')
                  ->references('id')
                  ->on('co_service_type_document_identifications');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sellers');
    }
}
