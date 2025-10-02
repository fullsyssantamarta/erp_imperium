<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS AP (Procedimientos)
 * Contiene la información de procedimientos realizados
 */
class RipsApProcedimientos extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_ap_procedimientos';
    
    protected $fillable = [
        'document_id',
        'numero_factura',
        'codigo_prestador',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'fecha_procedimiento',
        'numero_autorizacion',
        'codigo_procedimiento',
        'ambito_realizacion_procedimiento',
        'modalidad_grupo_servicio_terapeutico',
        'grupo_servicios',
        'servicios_solicitados',
        'diagnostico_principal',
        'diagnostico_relacionado',
        'complicacion',
        'forma_realizacion_acto_quirurgico',
        'valor_procedimiento',
        'valor_cuota_moderadora',
        'valor_neto_pagar'
    ];

    protected $casts = [
        'valor_procedimiento' => 'decimal:2',
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
     * Generar línea AP para archivo TXT
     */
    public function generarLineaAP()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f",
            $this->numero_factura,
            $this->codigo_prestador,
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->fecha_procedimiento,
            $this->numero_autorizacion ?: '',
            $this->codigo_procedimiento,
            $this->ambito_realizacion_procedimiento,
            $this->modalidad_grupo_servicio_terapeutico,
            $this->grupo_servicios,
            $this->servicios_solicitados,
            $this->diagnostico_principal,
            $this->diagnostico_relacionado ?: '',
            $this->complicacion ?: '',
            $this->forma_realizacion_acto_quirurgico,
            $this->valor_procedimiento,
            $this->valor_cuota_moderadora,
            $this->valor_neto_pagar
        );
    }

    /**
     * Validar datos del procedimiento
     */
    public function validarDatos()
    {
        $errores = [];

        if (empty($this->codigo_procedimiento)) {
            $errores[] = 'Código de procedimiento requerido';
        }

        if (empty($this->diagnostico_principal)) {
            $errores[] = 'Diagnóstico principal requerido';
        }

        if ($this->valor_procedimiento <= 0) {
            $errores[] = 'Valor del procedimiento debe ser mayor a 0';
        }

        return $errores;
    }
}
