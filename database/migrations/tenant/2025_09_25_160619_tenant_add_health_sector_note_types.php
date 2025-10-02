<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class TenantAddHealthSectorNoteTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Agregar nuevos tipos de documentos específicos para el sector salud
        DB::table('cat_document_types')->insert([
            [
                'id' => '91', 
                'active' => true, 
                'short' => 'NCH', 
                'description' => 'NOTA DE CRÉDITO SECTOR SALUD'
            ],
            [
                'id' => '92', 
                'active' => true, 
                'short' => 'NDH', 
                'description' => 'NOTA DE DÉBITO SECTOR SALUD'
            ]
        ]);

        // Agregar nuevos tipos de notas de crédito específicos para el sector salud
        DB::table('cat_note_credit_types')->insert([
            [
                'id' => '13', 
                'active' => true, 
                'description' => 'Devolución de servicios de salud no prestados'
            ],
            [
                'id' => '14', 
                'active' => true, 
                'description' => 'Anulación de procedimientos médicos'
            ],
            [
                'id' => '15', 
                'active' => true, 
                'description' => 'Corrección en información de paciente'
            ],
            [
                'id' => '16', 
                'active' => true, 
                'description' => 'Ajuste por glosa de EPS'
            ],
            [
                'id' => '17', 
                'active' => true, 
                'description' => 'Descuento por copago o cuota moderadora'
            ],
            [
                'id' => '18', 
                'active' => true, 
                'description' => 'Anulación por autorización no válida'
            ],
            [
                'id' => '19', 
                'active' => true, 
                'description' => 'Corrección en códigos CUPS o CIE-10'
            ],
            [
                'id' => '20', 
                'active' => true, 
                'description' => 'Ajuste por cambio de régimen de afiliación'
            ]
        ]);

        // Agregar nuevos tipos de notas de débito específicos para el sector salud
        DB::table('cat_note_debit_types')->insert([
            [
                'id' => '12', 
                'active' => true, 
                'description' => 'Cobro adicional por servicios complementarios'
            ],
            [
                'id' => '13', 
                'active' => true, 
                'description' => 'Ajuste por diferencia en valor de procedimiento'
            ],
            [
                'id' => '14', 
                'active' => true, 
                'description' => 'Recargo por atención fuera de red'
            ],
            [
                'id' => '15', 
                'active' => true, 
                'description' => 'Cobro por servicios no POS'
            ],
            [
                'id' => '16', 
                'active' => true, 
                'description' => 'Ajuste por corrección en copago'
            ],
            [
                'id' => '17', 
                'active' => true, 
                'description' => 'Recargo por urgencias no justificadas'
            ],
            [
                'id' => '18', 
                'active' => true, 
                'description' => 'Cobro adicional por medicamentos especiales'
            ]
        ]);

        // Agregar campo health_users a la tabla notes para relacionar usuarios de salud
        Schema::table('notes', function (Blueprint $table) {
            $table->json('health_users')->nullable()->after('note_description');
        });

        // Agregar tabla específica para relacionar notas con usuarios de salud
        Schema::create('note_health_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('note_id');
            $table->string('provider_code', 15)->nullable();
            $table->unsignedTinyInteger('health_type_document_identification_id')->nullable();
            $table->string('identification_number', 20);
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable();
            $table->string('surname', 60);
            $table->string('second_surname', 60)->nullable();
            $table->unsignedTinyInteger('health_type_user_id');
            $table->unsignedTinyInteger('health_contracting_payment_method_id');
            $table->unsignedTinyInteger('health_coverage_id');
            $table->string('autorization_numbers', 255)->nullable();
            $table->string('mipres', 255)->nullable();
            $table->string('mipres_delivery', 255)->nullable();
            $table->string('contract_number', 255)->nullable();
            $table->string('policy_number', 255)->nullable();
            $table->decimal('co_payment', 12, 2)->default(0);
            $table->decimal('moderating_fee', 12, 2)->default(0);
            $table->decimal('recovery_fee', 12, 2)->default(0);
            $table->decimal('shared_payment', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('note_id')->references('id')->on('notes')->onDelete('cascade');
            $table->index(['note_id']);
            $table->index(['identification_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar tabla de usuarios de salud para notas
        Schema::dropIfExists('note_health_users');

        // Eliminar campo health_users de la tabla notes
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('health_users');
        });

        // Eliminar tipos de notas de débito específicos del sector salud
        DB::table('cat_note_debit_types')->whereIn('id', [
            '12', '13', '14', '15', '16', '17', '18'
        ])->delete();

        // Eliminar tipos de notas de crédito específicos del sector salud
        DB::table('cat_note_credit_types')->whereIn('id', [
            '13', '14', '15', '16', '17', '18', '19', '20'
        ])->delete();

        // Eliminar tipos de documentos específicos del sector salud
        DB::table('cat_document_types')->whereIn('id', ['91', '92'])->delete();
    }
}