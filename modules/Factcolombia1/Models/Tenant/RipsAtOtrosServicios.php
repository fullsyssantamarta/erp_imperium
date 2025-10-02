<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS AT (Otros Servicios)
 * Contiene la información de otros servicios no incluidos en categorías anteriores
 */
class RipsAtOtrosServicios extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_at_otros_servicios';
    
    protected $fillable = [
        'document_id',
        'numero_factura',
        'codigo_prestador',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'numero_autorizacion',
        'tipo_servicio',
        'codigo_servicio',
        'nombre_servicio',
        'cantidad_servicio',
        'valor_unitario_servicio',
        'valor_total_servicio',
        'valor_cuota_moderadora',
        'valor_neto_pagar'
    ];

    protected $casts = [
        'cantidad_servicio' => 'decimal:2',
        'valor_unitario_servicio' => 'decimal:2',
        'valor_total_servicio' => 'decimal:2',
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
     * Generar línea AT para archivo TXT
     */
    public function generarLineaAT()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%.2f",
            $this->numero_factura,
            $this->codigo_prestador,
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->numero_autorizacion ?: '',
            $this->tipo_servicio,
            $this->codigo_servicio,
            $this->nombre_servicio,
            $this->cantidad_servicio,
            $this->valor_unitario_servicio,
            $this->valor_total_servicio,
            $this->valor_cuota_moderadora,
            $this->valor_neto_pagar
        );
    }

    /**
     * Validar datos de otros servicios
     */
    public function validarDatos()
    {
        $errores = [];

        if (empty($this->codigo_servicio)) {
            $errores[] = 'Código de servicio requerido';
        }

        if (empty($this->nombre_servicio)) {
            $errores[] = 'Nombre de servicio requerido';
        }

        if ($this->cantidad_servicio <= 0) {
            $errores[] = 'Cantidad de servicio debe ser mayor a 0';
        }

        if ($this->valor_unitario_servicio <= 0) {
            $errores[] = 'Valor unitario debe ser mayor a 0';
        }

        if ($this->valor_total_servicio <= 0) {
            $errores[] = 'Valor total debe ser mayor a 0';
        }

        return $errores;
    }
}
