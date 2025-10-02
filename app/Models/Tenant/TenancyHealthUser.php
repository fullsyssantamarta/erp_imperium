<?php

namespace App\Models\Tenant;

use Carbon\Carbon;

class TenancyHealthUser extends ModelTenant
{
    protected $table = 'tenancy_health_users';

    protected $fillable = [
        'documento',
        'tipo_documento',
        'primer_apellido',
        'segundo_apellido',
        'primer_nombre',
        'segundo_nombre',
        'nombre_completo',
        'telefono',
        'celular',
        'email',
        'direccion',
        'fecha_nacimiento',
        'edad',
        'genero',
        'estado_civil',
        'departamento',
        'municipio',
        'zona',
        'eps_codigo',
        'eps_nombre',
        'tipo_afiliacion',
        'regimen',
        'grupo_poblacional',
        'nivel_sisben',
        'discapacidad',
        'tipo_discapacidad',
        'codigo_cups',
        'descripcion_procedimiento',
        'cie10',
        'descripcion_diagnostico',
        'valor_procedimiento',
        'copago',
        'cuota_moderadora',
        'valor_neto',
        'retencion_fuente',
        'retencion_ica',
        'retencion_cree',
        'prestador_codigo',
        'prestador_nombre',
        'profesional_tratante',
        'registro_profesional',
        'fecha_atencion',
        'modalidad_atencion',
        'finalidad_consulta',
        'numero_autorizacion',
        'activo',
        'observaciones',
        'origen_dato',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_atencion' => 'datetime',
        'discapacidad' => 'boolean',
        'activo' => 'boolean',
        'valor_procedimiento' => 'decimal:2',
        'copago' => 'decimal:2',
        'cuota_moderadora' => 'decimal:2',
        'valor_neto' => 'decimal:2',
        'retencion_fuente' => 'decimal:2',
        'retencion_ica' => 'decimal:2',
        'retencion_cree' => 'decimal:2',
    ];

    /**
     * Buscar usuario por documento
     */
    public static function findByDocument($documento, $tipo_documento = 'CC')
    {
        return self::where('documento', $documento)
                  ->where('tipo_documento', $tipo_documento)
                  ->where('activo', true)
                  ->first();
    }

    /**
     * Obtener nombre completo calculado
     */
    public function getNombreCompletoAttribute($value)
    {
        if ($value) {
            return $value;
        }

        $nombres = array_filter([
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido
        ]);

        return implode(' ', $nombres);
    }

    /**
     * Calcular edad automáticamente
     */
    public function getEdadCalculadaAttribute()
    {
        if ($this->fecha_nacimiento) {
            return Carbon::parse($this->fecha_nacimiento)->age;
        }
        return $this->edad;
    }

    /**
     * Obtener valor neto calculado
     */
    public function getValorNetoCalculadoAttribute()
    {
        if ($this->valor_neto !== null) {
            return $this->valor_neto;
        }

        $valor_base = $this->valor_procedimiento ?? 0;
        $descuentos = ($this->copago ?? 0) + ($this->cuota_moderadora ?? 0);
        $retenciones = ($this->retencion_fuente ?? 0) + ($this->retencion_ica ?? 0) + ($this->retencion_cree ?? 0);

        return $valor_base - $descuentos - $retenciones;
    }

    /**
     * Scope para usuarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por EPS
     */
    public function scopeByEps($query, $eps_codigo)
    {
        return $query->where('eps_codigo', $eps_codigo);
    }

    /**
     * Scope para buscar por rango de fechas de atención
     */
    public function scopeByFechaAtencion($query, $fecha_inicio, $fecha_fin)
    {
        return $query->whereBetween('fecha_atencion', [$fecha_inicio, $fecha_fin]);
    }

    /**
     * Scope para buscar por prestador
     */
    public function scopeByPrestador($query, $prestador_codigo)
    {
        return $query->where('prestador_codigo', $prestador_codigo);
    }

    /**
     * Obtener datos para facturación
     */
    public function getDatosFacturacion()
    {
        return [
            'cliente' => [
                'documento' => $this->documento,
                'tipo_documento' => $this->tipo_documento,
                'nombre' => $this->nombre_completo,
                'telefono' => $this->telefono ?? $this->celular,
                'email' => $this->email,
                'direccion' => $this->direccion,
                'departamento' => $this->departamento,
                'municipio' => $this->municipio,
            ],
            'servicios' => [
                'codigo_cups' => $this->codigo_cups,
                'descripcion' => $this->descripcion_procedimiento,
                'valor' => $this->valor_procedimiento,
                'cie10' => $this->cie10,
                'diagnostico' => $this->descripcion_diagnostico,
            ],
            'retenciones' => [
                'retencion_fuente' => $this->retencion_fuente,
                'retencion_ica' => $this->retencion_ica,
                'retencion_cree' => $this->retencion_cree,
            ],
            'eps' => [
                'codigo' => $this->eps_codigo,
                'nombre' => $this->eps_nombre,
                'tipo_afiliacion' => $this->tipo_afiliacion,
                'regimen' => $this->regimen,
            ]
        ];
    }

    /**
     * Agrupar por número de factura para múltiples servicios
     */
    public static function agruparPorFactura($documento, $fecha_atencion = null)
    {
        $query = self::where('documento', $documento)->where('activo', true);
        
        if ($fecha_atencion) {
            $query->whereDate('fecha_atencion', $fecha_atencion);
        }
        
        return $query->get();
    }
}
