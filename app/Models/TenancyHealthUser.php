<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TenancyHealthUser extends Model
{
    use HasFactory;

    protected $table = 'tenancy_health_users';

    protected $fillable = [
        'tipo_documento_identificacion',
        'numero_documento_identificacion',
        'primer_apellido',
        'segundo_apellido',
        'primer_nombre',
        'segundo_nombre',
        'fecha_nacimiento',
        'codigo_sexo',
        'eps',
        'tipo_usuario',
        'tipo_usuario_id',
        'modalidad_contratacion',
        'modalidad_contratacion_id',
        'cobertura_plan_beneficios',
        'cobertura_plan_beneficios_id',
        'codigo_pais_residencia',
        'codigo_municipio_residencia',
        'codigo_zona_territorial_residencia',
        'telefono',
        'email',
        'direccion',
        'codigo_diagnostico_principal',
        'codigo_diagnostico_relacionado',
        'codigo_diagnostico_relacionado2',
        'datos_adicionales',
        'activo',
        'fecha_ultima_actualizacion'
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
        'fecha_nacimiento' => 'date',
        'fecha_ultima_actualizacion' => 'datetime',
        'activo' => 'boolean'
    ];

    /**
     * Buscar usuario por número de documento
     */
    public static function findByDocument($numeroDocumento)
    {
        return self::where('numero_documento_identificacion', $numeroDocumento)
                  ->where('activo', true)
                  ->first();
    }

    /**
     * Obtener nombre completo
     */
    public function getNombreCompletoAttribute()
    {
        $nombres = array_filter([
            $this->primer_nombre,
            $this->segundo_nombre
        ]);
        
        $apellidos = array_filter([
            $this->primer_apellido,
            $this->segundo_apellido
        ]);

        return implode(' ', array_merge($nombres, $apellidos));
    }

    /**
     * Calcular edad basada en fecha de nacimiento
     */
    public function getEdadAttribute()
    {
        if ($this->fecha_nacimiento) {
            return Carbon::parse($this->fecha_nacimiento)->age;
        }
        return null;
    }

    /**
     * Scope para filtrar por EPS
     */
    public function scopeByEps($query, $eps)
    {
        return $query->where('eps', $eps);
    }

    /**
     * Scope para filtrar por tipo de usuario
     */
    public function scopeByTipoUsuario($query, $tipo)
    {
        return $query->where('tipo_usuario', $tipo);
    }

    /**
     * Obtener datos formateados para facturación
     */
    public function getDatosFacturacion()
    {
        return [
            'tipo_documento' => $this->tipo_documento_identificacion,
            'numero_documento' => $this->numero_documento_identificacion,
            'nombre_completo' => $this->nombre_completo,
            'eps' => $this->eps,
            'tipo_usuario' => $this->tipo_usuario,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'codigo_sexo' => $this->codigo_sexo,
            'codigo_municipio' => $this->codigo_municipio_residencia,
            'diagnostico_principal' => $this->codigo_diagnostico_principal
        ];
    }
}
