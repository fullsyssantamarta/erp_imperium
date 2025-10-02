<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemainingRipsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 3. Tabla US - Usuarios
        if (!Schema::hasTable('rips_us_usuarios')) {
            Schema::create('rips_us_usuarios', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tipo_documento_identificacion', 2);
                $table->string('numero_documento_identificacion', 20);
                $table->string('tipo_usuario', 2);
                $table->string('primer_apellido', 60);
                $table->string('segundo_apellido', 60)->nullable();
                $table->string('primer_nombre', 60);
                $table->string('segundo_nombre', 60)->nullable();
                $table->date('fecha_nacimiento');
                $table->string('sexo', 1);
                $table->string('codigo_entidad_administradora', 6);
                $table->string('tipo_afiliado', 2);
                $table->string('ips_primaria_habitual', 12)->nullable();
                $table->timestamps();
                
                $table->index(['numero_documento_identificacion'], 'rips_us_doc_idx');
            });
        }

        // 4. Tabla AC - Consultas
        if (!Schema::hasTable('rips_ac_consultas')) {
            Schema::create('rips_ac_consultas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_factura', 20);
                $table->string('codigo_prestador', 12);
                $table->string('tipo_documento_identificacion', 2);
                $table->string('numero_documento_identificacion', 20);
                $table->date('fecha_consulta');
                $table->string('numero_autorizacion', 15)->nullable();
                $table->string('codigo_consulta', 8);
                $table->string('modalidad_grupo_servicio_terapeutico', 2);
                $table->string('grupo_servicios', 2);
                $table->string('codigo_servicio', 3);
                $table->string('finalidad_tecnologia_salud', 2);
                $table->string('causa_motivo_atencion', 2);
                $table->string('codigo_diagnostico_principal', 4);
                $table->string('codigo_diagnostico_relacionado1', 4)->nullable();
                $table->string('codigo_diagnostico_relacionado2', 4)->nullable();
                $table->string('codigo_diagnostico_relacionado3', 4)->nullable();
                $table->string('tipo_diagnostico_principal', 1);
                $table->decimal('valor_consulta', 16, 2);
                $table->decimal('valor_cuota_moderadora', 16, 2)->default(0);
                $table->decimal('valor_neto_pagar', 16, 2);
                $table->timestamps();
                
                $table->index(['numero_factura'], 'rips_ac_factura_idx');
                $table->index(['numero_documento_identificacion'], 'rips_ac_doc_idx');
            });
        }

        // 5. Tabla AP - Procedimientos
        if (!Schema::hasTable('rips_ap_procedimientos')) {
            Schema::create('rips_ap_procedimientos', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_factura', 20);
                $table->string('codigo_prestador', 12);
                $table->string('tipo_documento_identificacion', 2);
                $table->string('numero_documento_identificacion', 20);
                $table->date('fecha_procedimiento');
                $table->string('numero_autorizacion', 15)->nullable();
                $table->string('codigo_procedimiento', 8);
                $table->string('ambito_realizacion_procedimiento', 1);
                $table->string('modalidad_grupo_servicio_terapeutico', 2);
                $table->string('grupo_servicios', 2);
                $table->string('codigo_servicio', 3);
                $table->string('finalidad_tecnologia_salud', 2);
                $table->string('personal_atiende', 2);
                $table->string('codigo_diagnostico_principal', 4);
                $table->string('codigo_diagnostico_relacionado', 4)->nullable();
                $table->string('codigo_complicacion', 4)->nullable();
                $table->string('forma_realizacion_acto_quirurgico', 1)->nullable();
                $table->decimal('valor_procedimiento', 16, 2);
                $table->timestamps();
                
                $table->index(['numero_factura'], 'rips_ap_factura_idx');
                $table->index(['numero_documento_identificacion'], 'rips_ap_doc_idx');
            });
        }

        // 6. Tabla AU - Urgencias
        if (!Schema::hasTable('rips_au_urgencias')) {
            Schema::create('rips_au_urgencias', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_factura', 20);
                $table->string('codigo_prestador', 12);
                $table->string('tipo_documento_identificacion', 2);
                $table->string('numero_documento_identificacion', 20);
                $table->date('fecha_ingreso_urgencias');
                $table->time('hora_ingreso_urgencias');
                $table->string('numero_autorizacion', 15)->nullable();
                $table->string('causa_externa', 2);
                $table->string('codigo_diagnostico_salida', 4);
                $table->string('codigo_diagnostico_relacionado1', 4)->nullable();
                $table->string('codigo_diagnostico_relacionado2', 4)->nullable();
                $table->string('codigo_diagnostico_relacionado3', 4)->nullable();
                $table->string('destino_usuario_urgencias', 2);
                $table->string('estado_salida', 2);
                $table->string('codigo_diagnostico_muerte', 4)->nullable();
                $table->date('fecha_salida_urgencias')->nullable();
                $table->time('hora_salida_urgencias')->nullable();
                $table->timestamps();
                
                $table->index(['numero_factura'], 'rips_au_factura_idx');
                $table->index(['numero_documento_identificacion'], 'rips_au_doc_idx');
            });
        }

        // 7. Tabla AH - Hospitalización
        if (!Schema::hasTable('rips_ah_hospitalizacion')) {
            Schema::create('rips_ah_hospitalizacion', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_factura', 20);
                $table->string('codigo_prestador', 12);
                $table->string('tipo_documento_identificacion', 2);
                $table->string('numero_documento_identificacion', 20);
                $table->string('via_ingreso_institucion', 1);
                $table->date('fecha_ingreso_hospitalizacion');
                $table->time('hora_ingreso_hospitalizacion');
                $table->string('numero_autorizacion', 15)->nullable();
                $table->string('causa_externa', 2);
                $table->string('codigo_diagnostico_principal_ingreso', 4);
                $table->string('codigo_diagnostico_principal_egreso', 4);
                $table->string('codigo_diagnostico_relacionado1_egreso', 4)->nullable();
                $table->string('codigo_diagnostico_relacionado2_egreso', 4)->nullable();
                $table->string('codigo_diagnostico_relacionado3_egreso', 4)->nullable();
                $table->string('codigo_diagnostico_complicacion', 4)->nullable();
                $table->string('codigo_diagnostico_muerte', 4)->nullable();
                $table->date('fecha_egreso_hospitalizacion');
                $table->time('hora_egreso_hospitalizacion');
                $table->string('destino_egreso_hospitalizacion', 2);
                $table->string('estado_salida', 2);
                $table->timestamps();
                
                $table->index(['numero_factura'], 'rips_ah_factura_idx');
                $table->index(['numero_documento_identificacion'], 'rips_ah_doc_idx');
            });
        }

        // 8. Tabla AN - Recién Nacidos
        if (!Schema::hasTable('rips_an_recien_nacidos')) {
            Schema::create('rips_an_recien_nacidos', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_factura', 20);
                $table->string('codigo_prestador', 12);
                $table->string('tipo_documento_identificacion_madre', 2);
                $table->string('numero_documento_identificacion_madre', 20);
                $table->date('fecha_nacimiento');
                $table->time('hora_nacimiento');
                $table->integer('numero_consulta_prenatal');
                $table->integer('semanas_gestacion_atencion_parto');
                $table->string('tipo_parto', 1);
                $table->integer('multiplicidad_embarazo');
                $table->string('numero_nacidos_vivos', 1);
                $table->string('numero_nacidos_muertos', 1);
                $table->string('sexo_recien_nacido', 1);
                $table->integer('peso_recien_nacido');
                $table->string('codigo_diagnostico_recien_nacido', 4);
                $table->string('causa_basica_muerte', 4)->nullable();
                $table->date('fecha_muerte')->nullable();
                $table->time('hora_muerte')->nullable();
                $table->timestamps();
                
                $table->index(['numero_factura'], 'rips_an_factura_idx');
                $table->index(['numero_documento_identificacion_madre'], 'rips_an_doc_madre_idx');
            });
        }

        // 9. Tabla AM - Medicamentos
        if (!Schema::hasTable('rips_am_medicamentos')) {
            Schema::create('rips_am_medicamentos', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_factura', 20);
                $table->string('codigo_prestador', 12);
                $table->string('tipo_documento_identificacion', 2);
                $table->string('numero_documento_identificacion', 20);
                $table->string('numero_autorizacion', 15)->nullable();
                $table->string('codigo_medicamento', 20);
                $table->string('tipo_medicamento', 1);
                $table->string('nombre_generico_medicamento', 30)->nullable();
                $table->string('forma_farmaceutica', 4)->nullable();
                $table->string('concentracion_dosificacion', 20)->nullable();
                $table->string('unidad_medida_medicamento', 20)->nullable();
                $table->string('numero_unidades', 8);
                $table->decimal('valor_unitario_medicamento', 16, 2);
                $table->decimal('valor_total_medicamento', 16, 2);
                $table->timestamps();
                
                $table->index(['numero_factura'], 'rips_am_factura_idx');
                $table->index(['numero_documento_identificacion'], 'rips_am_doc_idx');
            });
        }

        // 10. Tabla AT - Otros Servicios
        if (!Schema::hasTable('rips_at_otros_servicios')) {
            Schema::create('rips_at_otros_servicios', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('numero_factura', 20);
                $table->string('codigo_prestador', 12);
                $table->string('tipo_documento_identificacion', 2);
                $table->string('numero_documento_identificacion', 20);
                $table->string('numero_autorizacion', 15)->nullable();
                $table->string('tipo_servicio', 2);
                $table->string('codigo_servicio_tecnologia', 20);
                $table->string('nombre_servicio_tecnologia', 60)->nullable();
                $table->integer('cantidad_servicio');
                $table->decimal('valor_unitario_servicio', 16, 2);
                $table->decimal('valor_total_servicio', 16, 2);
                $table->timestamps();
                
                $table->index(['numero_factura'], 'rips_at_factura_idx');
                $table->index(['numero_documento_identificacion'], 'rips_at_doc_idx');
            });
        }

        // 11. Tabla de Control de Generación
        if (!Schema::hasTable('rips_generation_control')) {
            Schema::create('rips_generation_control', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('document_id');
                $table->string('numero_remision', 15);
                $table->date('fecha_generacion');
                $table->integer('total_registros');
                $table->decimal('total_valor', 16, 2);
                $table->string('estado_validacion', 20)->default('pendiente');
                $table->text('resultado_validacion')->nullable();
                $table->string('cuv_fevrips', 50)->nullable();
                $table->json('archivos_generados')->nullable();
                $table->timestamps();
                
                $table->index(['document_id'], 'rips_ctrl_doc_idx');
                $table->index(['numero_remision'], 'rips_ctrl_remision_idx');
                $table->index(['estado_validacion'], 'rips_ctrl_estado_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tables = [
            'rips_generation_control',
            'rips_at_otros_servicios',
            'rips_am_medicamentos', 
            'rips_an_recien_nacidos',
            'rips_ah_hospitalizacion',
            'rips_au_urgencias',
            'rips_ap_procedimientos',
            'rips_ac_consultas',
            'rips_us_usuarios'
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
}
