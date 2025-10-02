<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS AU (Urgencias)
 * Contiene la información de atenciones de urgencias
 */
class RipsAuUrgencias extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_au_urgencias';
    
    protected $fillable = [
        'document_id',
        'numero_factura',
        'codigo_prestador',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'fecha_ingreso',
        'hora_ingreso',
        'numero_autorizacion',
        'causa_externa',
        'diagnostico_salida',
        'diagnostico_relacionado1',
        'diagnostico_relacionado2',
        'diagnostico_relacionado3',
        'destino_usuario_egreso',
        'estado_salida',
        'valor_urgencia',
        'valor_cuota_moderadora',
        'valor_neto_pagar'
    ];

    protected $casts = [
        'valor_urgencia' => 'decimal:2',
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
     * Generar línea AU para archivo TXT
     */
    public function generarLineaAU()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f",
            $this->numero_factura,
            $this->codigo_prestador,
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->fecha_ingreso,
            $this->hora_ingreso,
            $this->numero_autorizacion ?: '',
            $this->causa_externa,
            $this->diagnostico_salida,
            $this->diagnostico_relacionado1 ?: '',
            $this->diagnostico_relacionado2 ?: '',
            $this->diagnostico_relacionado3 ?: '',
            $this->destino_usuario_egreso,
            $this->estado_salida,
            $this->valor_urgencia,
            $this->valor_cuota_moderadora,
            $this->valor_neto_pagar
        );
    }

    /**
     * Validar datos de urgencia
     */
    public function validarDatos()
    {
        $errores = [];

        if (empty($this->diagnostico_salida)) {
            $errores[] = 'Diagnóstico de salida requerido';
        }

        if (!in_array($this->causa_externa, ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'])) {
            $errores[] = 'Causa externa inválida';
        }

        if ($this->valor_urgencia <= 0) {
            $errores[] = 'Valor de urgencia debe ser mayor a 0';
        }

        return $errores;
    }
}
