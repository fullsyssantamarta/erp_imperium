<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenancyHealthUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenancy_health_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Identificación del paciente
            $table->string('documento', 20)->index(); // Número de documento/cédula
            $table->string('tipo_documento', 5)->default('CC'); // CC, TI, CE, etc.
            
            // Datos personales básicos
            $table->string('primer_apellido', 100)->nullable();
            $table->string('segundo_apellido', 100)->nullable();
            $table->string('primer_nombre', 100)->nullable();
            $table->string('segundo_nombre', 100)->nullable();
            $table->string('nombre_completo', 400)->nullable(); // Campo calculado
            
            // Información de contacto
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('direccion')->nullable();
            
            // Información demográfica
            $table->date('fecha_nacimiento')->nullable();
            $table->integer('edad')->nullable();
            $table->enum('genero', ['M', 'F', 'O'])->nullable(); // Masculino, Femenino, Otro
            $table->string('estado_civil', 20)->nullable();
            
            // Información de ubicación
            $table->string('departamento', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('zona', 20)->nullable(); // Urbana, Rural
            
            // Información de salud/EPS
            $table->string('eps_codigo', 10)->nullable();
            $table->string('eps_nombre', 200)->nullable();
            $table->string('tipo_afiliacion', 50)->nullable(); // Contributivo, Subsidiado, etc.
            $table->string('regimen', 50)->nullable();
            
            // Información adicional del sector salud
            $table->string('grupo_poblacional', 100)->nullable();
            $table->string('nivel_sisben', 10)->nullable();
            $table->boolean('discapacidad')->default(false);
            $table->string('tipo_discapacidad', 100)->nullable();
            
            // Información para facturación
            $table->string('codigo_cups', 20)->nullable(); // Código del procedimiento
            $table->string('descripcion_procedimiento', 500)->nullable();
            $table->string('cie10', 20)->nullable(); // Código CIE10 diagnóstico
            $table->string('descripcion_diagnostico', 500)->nullable();
            
            // Información financiera
            $table->decimal('valor_procedimiento', 12, 2)->nullable();
            $table->decimal('copago', 12, 2)->default(0);
            $table->decimal('cuota_moderadora', 12, 2)->default(0);
            $table->decimal('valor_neto', 12, 2)->nullable();
            
            // Retenciones e impuestos específicos del sector salud
            $table->decimal('retencion_fuente', 12, 2)->default(0);
            $table->decimal('retencion_ica', 12, 2)->default(0);
            $table->decimal('retencion_cree', 12, 2)->default(0);
            
            // Información del proveedor de servicios
            $table->string('prestador_codigo', 20)->nullable();
            $table->string('prestador_nombre', 200)->nullable();
            $table->string('profesional_tratante', 200)->nullable();
            $table->string('registro_profesional', 50)->nullable();
            
            // Información de la consulta/procedimiento
            $table->datetime('fecha_atencion')->nullable();
            $table->string('modalidad_atencion', 50)->nullable(); // Consulta externa, urgencias, etc.
            $table->string('finalidad_consulta', 50)->nullable();
            $table->integer('numero_autorizacion')->nullable();
            
            // Campos de control
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->string('origen_dato', 100)->default('EXCEL_IMPORT'); // Para rastrear origen
            
            // Campos de auditoria
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            
            // Indices para optimización
            $table->index(['documento', 'tipo_documento'], 'idx_documento_tipo');
            $table->index(['eps_codigo'], 'idx_eps');
            $table->index(['fecha_atencion'], 'idx_fecha_atencion');
            $table->index(['codigo_cups'], 'idx_cups');
            $table->index(['prestador_codigo'], 'idx_prestador');
            $table->index(['activo'], 'idx_activo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tenancy_health_users');
    }
}
