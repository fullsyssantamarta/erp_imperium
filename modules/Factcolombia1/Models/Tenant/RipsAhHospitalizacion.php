<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS AH (Hospitalización)
 * Contiene la información de hospitalizaciones
 */
class RipsAhHospitalizacion extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_ah_hospitalizacion';
    
    protected $fillable = [
        'document_id',
        'numero_factura',
        'codigo_prestador',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'via_ingreso_servicio_salud',
        'fecha_ingreso',
        'hora_ingreso',
        'numero_autorizacion',
        'causa_externa',
        'diagnostico_ingreso',
        'diagnostico_egreso',
        'diagnostico_relacionado1',
        'diagnostico_relacionado2',
        'diagnostico_relacionado3',
        'diagnostico_complicacion',
        'estado_salida',
        'diagnostico_muerte',
        'fecha_egreso',
        'hora_egreso',
        'valor_hospitalizacion',
        'valor_cuota_moderadora',
        'valor_neto_pagar'
    ];

    protected $casts = [
        'valor_hospitalizacion' => 'decimal:2',
        'valor_cuota_moderadora' => 'decimal:2',
        'valor_neto_pagar' => 'decimal:2'
    ];

    /**
     * Relación con el documento
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Generar línea AH para archivo TXT
     */
    public function generarLineaAH()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f",
            $this->numero_factura,
            $this->codigo_prestador,
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->via_ingreso_servicio_salud,
            $this->fecha_ingreso,
            $this->hora_ingreso,
            $this->numero_autorizacion ?: '',
            $this->causa_externa,
            $this->diagnostico_ingreso,
            $this->diagnostico_egreso,
            $this->diagnostico_relacionado1 ?: '',
            $this->diagnostico_relacionado2 ?: '',
            $this->diagnostico_relacionado3 ?: '',
            $this->diagnostico_complicacion ?: '',
            $this->estado_salida,
            $this->diagnostico_muerte ?: '',
            $this->fecha_egreso,
            $this->hora_egreso,
            $this->valor_hospitalizacion,
            $this->valor_cuota_moderadora,
            $this->valor_neto_pagar
        );
    }

    /**
     * Validar datos de hospitalización
     */
    public function validarDatos()
    {
        $errores = [];

        if (empty($this->diagnostico_ingreso)) {
            $errores[] = 'Diagnóstico de ingreso requerido';
        }

        if (empty($this->diagnostico_egreso)) {
            $errores[] = 'Diagnóstico de egreso requerido';
        }

        if ($this->valor_hospitalizacion <= 0) {
            $errores[] = 'Valor de hospitalización debe ser mayor a 0';
        }

        return $errores;
    }
}
