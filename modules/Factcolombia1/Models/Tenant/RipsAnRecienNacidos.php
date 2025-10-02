<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS AN (Recién Nacidos)
 * Contiene la información de atención a recién nacidos
 */
class RipsAnRecienNacidos extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_an_recien_nacidos';
    
    protected $fillable = [
        'document_id',
        'numero_factura',
        'codigo_prestador',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'fecha_nacimiento',
        'hora_nacimiento',
        'numero_autorizacion',
        'sexo',
        'peso',
        'diagnostico_recien_nacido',
        'diagnostico_relacionado',
        'tipo_diagnostico_principal',
        'estado_salida',
        'fecha_egreso',
        'hora_egreso',
        'valor_atencion',
        'valor_cuota_moderadora',
        'valor_neto_pagar'
    ];

    protected $casts = [
        'peso' => 'integer',
        'valor_atencion' => 'decimal:2',
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
     * Generar línea AN para archivo TXT
     */
    public function generarLineaAN()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f",
            $this->numero_factura,
            $this->codigo_prestador,
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->fecha_nacimiento,
            $this->hora_nacimiento,
            $this->numero_autorizacion ?: '',
            $this->sexo,
            $this->peso,
            $this->diagnostico_recien_nacido,
            $this->diagnostico_relacionado ?: '',
            $this->tipo_diagnostico_principal,
            $this->estado_salida,
            $this->fecha_egreso,
            $this->hora_egreso,
            $this->valor_atencion,
            $this->valor_cuota_moderadora,
            $this->valor_neto_pagar
        );
    }

    /**
     * Validar datos de recién nacido
     */
    public function validarDatos()
    {
        $errores = [];

        if (!in_array($this->sexo, ['M', 'F'])) {
            $errores[] = 'Sexo debe ser M o F';
        }

        if ($this->peso <= 0 || $this->peso > 10000) {
            $errores[] = 'Peso fuera de rango válido (1-10000 gramos)';
        }

        if (empty($this->diagnostico_recien_nacido)) {
            $errores[] = 'Diagnóstico de recién nacido requerido';
        }

        if ($this->valor_atencion <= 0) {
            $errores[] = 'Valor de atención debe ser mayor a 0';
        }

        return $errores;
    }
}
