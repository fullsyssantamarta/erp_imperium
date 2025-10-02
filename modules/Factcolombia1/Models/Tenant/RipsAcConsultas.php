<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS AC (Consultas)
 * Contiene la información de consultas realizadas
 */
class RipsAcConsultas extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_ac_consultas';
    
    protected $fillable = [
        'document_id',
        'numero_factura',
        'codigo_prestador',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'fecha_consulta',
        'numero_autorizacion',
        'codigo_consulta',
        'modalidad_grupo_servicio_terapeutico',
        'grupo_servicios',
        'servicios_solicitados',
        'diagnostico_principal',
        'diagnostico_relacionado1',
        'diagnostico_relacionado2',
        'diagnostico_relacionado3',
        'tipo_diagnostico_principal',
        'valor_consulta',
        'valor_cuota_moderadora',
        'valor_neto_pagar'
    ];

    protected $casts = [
        'valor_consulta' => 'decimal:2',
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
     * Generar línea AC para archivo TXT
     */
    public function generarLineaAC()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f",
            $this->numero_factura,
            $this->codigo_prestador,
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->fecha_consulta,
            $this->numero_autorizacion ?: '',
            $this->codigo_consulta,
            $this->modalidad_grupo_servicio_terapeutico,
            $this->grupo_servicios,
            $this->servicios_solicitados,
            $this->diagnostico_principal,
            $this->diagnostico_relacionado1 ?: '',
            $this->diagnostico_relacionado2 ?: '',
            $this->diagnostico_relacionado3 ?: '',
            $this->tipo_diagnostico_principal,
            $this->valor_consulta,
            $this->valor_cuota_moderadora,
            $this->valor_neto_pagar
        );
    }

    /**
     * Validar datos de la consulta
     */
    public function validarDatos()
    {
        $errores = [];

        if (empty($this->codigo_consulta)) {
            $errores[] = 'Código de consulta requerido';
        }

        if (empty($this->diagnostico_principal)) {
            $errores[] = 'Diagnóstico principal requerido';
        }

        if ($this->valor_consulta <= 0) {
            $errores[] = 'Valor de consulta debe ser mayor a 0';
        }

        return $errores;
    }
}
