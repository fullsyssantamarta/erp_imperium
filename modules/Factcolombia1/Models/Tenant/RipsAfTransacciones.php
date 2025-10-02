<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para Transacciones RIPS (AF)
 * Basado en Resolución 3374 de 2000
 */
class RipsAfTransacciones extends Model
{
    protected $table = 'rips_af_transacciones';
    
    protected $fillable = [
        'document_id',
        'codigo_prestador',
        'razon_social_prestador',
        'tipo_identificacion_prestador',
        'numero_identificacion_prestador',
        'numero_factura',
        'fecha_expedicion_factura',
        'fecha_inicio_periodo_facturado',
        'fecha_final_periodo_facturado',
        'codigo_entidad_administradora',
        'nombre_entidad_administradora',
        'numero_contrato',
        'plan_beneficios',
        'numero_poliza',
        'valor_total_pagado_entidad',
        'valor_comision_entidad',
        'valor_descuentos_entidad',
        'valor_neto_pagado_entidad'
    ];

    protected $casts = [
        'valor_total_pagado_entidad' => 'decimal:2',
        'valor_comision_entidad' => 'decimal:2',
        'valor_descuentos_entidad' => 'decimal:2',
        'valor_neto_pagado_entidad' => 'decimal:2'
    ];

    /**
     * Relación con documento
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Generar línea para archivo AF
     */
    public function generarLineaAF()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f,%.2f",
            $this->codigo_prestador,
            $this->razon_social_prestador,
            $this->tipo_identificacion_prestador,
            $this->numero_identificacion_prestador,
            $this->numero_factura,
            $this->fecha_expedicion_factura,
            $this->fecha_inicio_periodo_facturado,
            $this->fecha_final_periodo_facturado,
            $this->codigo_entidad_administradora,
            $this->nombre_entidad_administradora,
            $this->numero_contrato,
            $this->plan_beneficios,
            $this->numero_poliza ?? '',
            $this->valor_total_pagado_entidad,
            $this->valor_comision_entidad ?? 0,
            $this->valor_descuentos_entidad ?? 0,
            $this->valor_neto_pagado_entidad
        );
    }
}
