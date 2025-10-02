<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para archivo RIPS US (Usuarios)
 * Contiene la información de los usuarios atendidos
 */
class RipsUsUsuarios extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_us_usuarios';
    
    protected $fillable = [
        'document_id',
        'tipo_identificacion_usuario',
        'numero_identificacion_usuario',
        'codigo_entidad_administradora',
        'tipo_usuario',
        'primer_apellido',
        'segundo_apellido',
        'primer_nombre',
        'segundo_nombre',
        'edad',
        'unidad_medida_edad',
        'sexo',
        'codigo_departamento',
        'codigo_municipio',
        'zona_residencia'
    ];

    protected $casts = [
        'edad' => 'integer'
    ];

    /**
     * Relación con el documento
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Generar línea US para archivo TXT
     */
    public function generarLineaUS()
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s,%s",
            $this->tipo_identificacion_usuario,
            $this->numero_identificacion_usuario,
            $this->codigo_entidad_administradora,
            $this->tipo_usuario,
            $this->primer_apellido,
            $this->segundo_apellido ?: '',
            $this->primer_nombre,
            $this->segundo_nombre ?: '',
            $this->edad,
            $this->unidad_medida_edad,
            $this->sexo,
            $this->codigo_departamento,
            $this->codigo_municipio,
            $this->zona_residencia
        );
    }

    /**
     * Validar datos del usuario según normativa
     */
    public function validarDatos()
    {
        $errores = [];

        if (!in_array($this->tipo_identificacion_usuario, ['RC', 'TI', 'CC', 'CE', 'PA', 'MS', 'AS'])) {
            $errores[] = 'Tipo de identificación inválido';
        }

        if (empty($this->numero_identificacion_usuario)) {
            $errores[] = 'Número de identificación requerido';
        }

        if (!in_array($this->sexo, ['M', 'F'])) {
            $errores[] = 'Sexo debe ser M o F';
        }

        if ($this->edad < 0 || $this->edad > 150) {
            $errores[] = 'Edad fuera de rango válido';
        }

        return $errores;
    }
}
