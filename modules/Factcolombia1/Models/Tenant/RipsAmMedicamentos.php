<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS AM (Medicamentos)
 * Contiene la información de medicamentos suministrados
 */
class RipsAmMedicamentos extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_am_medicamentos';
    
    protected $fillable = [
        'document_id',
        'numero_factura',
        'codigo_prestador',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'numero_autorizacion',
        'codigo_medicamento',
        'tipo_medicamento',
        'nombre_medicamento',
        'forma_farmaceutica',
        'concentracion_medicamento',
        'unidad_medida_medicamento',
        'numero_unidades',
        'valor_unitario_medicamento',
        'valor_total_medicamento',
        'valor_cuota_moderadora',
        'valor_neto_pagar'
    ];

    protected $casts = [
        'numero_unidades' => 'decimal:2',
        'valor_unitario_medicamento' => 'decimal:2',
        'valor_total_medicamento' => 'decimal:2',
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
     * Generar línea AM para archivo TXT
     */
    public function generarLineaAM()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%.2f",
            $this->numero_factura,
            $this->codigo_prestador,
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->numero_autorizacion ?: '',
            $this->codigo_medicamento,
            $this->tipo_medicamento,
            $this->nombre_medicamento,
            $this->forma_farmaceutica,
            $this->concentracion_medicamento,
            $this->unidad_medida_medicamento,
            $this->numero_unidades,
            $this->valor_unitario_medicamento,
            $this->valor_total_medicamento,
            $this->valor_cuota_moderadora,
            $this->valor_neto_pagar
        );
    }

    /**
     * Validar datos de medicamento
     */
    public function validarDatos()
    {
        $errores = [];

        if (empty($this->codigo_medicamento)) {
            $errores[] = 'Código de medicamento requerido';
        }

        if (empty($this->nombre_medicamento)) {
            $errores[] = 'Nombre de medicamento requerido';
        }

        if ($this->numero_unidades <= 0) {
            $errores[] = 'Número de unidades debe ser mayor a 0';
        }

        if ($this->valor_unitario_medicamento <= 0) {
            $errores[] = 'Valor unitario debe ser mayor a 0';
        }

        if ($this->valor_total_medicamento <= 0) {
            $errores[] = 'Valor total debe ser mayor a 0';
        }

        return $errores;
    }
}
