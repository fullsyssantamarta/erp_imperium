<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

/**
 * Modelo para control de generación de RIPS
 * Registra el proceso de generación y estado de archivos RIPS
 */
class RipsGenerationControl extends Model
{
    use UsesTenantConnection;

    protected $table = 'rips_generation_control';
    
    protected $fillable = [
        'document_id',
        'numero_remision',
        'codigo_prestador',
        'fecha_generacion',
        'estado',
        'archivos_generados',
        'errores_validacion',
        'observaciones'
    ];

    protected $casts = [
        'archivos_generados' => 'array',
        'errores_validacion' => 'array'
    ];

    /**
     * Relación con el documento
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Scope para obtener generaciones exitosas
     */
    public function scopeGenerado($query)
    {
        return $query->where('estado', 'generado');
    }

    /**
     * Scope para obtener generaciones pendientes
     */
    public function scopePendiente($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para obtener generaciones rechazadas
     */
    public function scopeRechazado($query)
    {
        return $query->where('estado', 'rechazado');
    }

    /**
     * Verificar si la generación fue exitosa
     */
    public function esExitosa()
    {
        return $this->estado === 'generado';
    }

    /**
     * Obtener resumen de archivos generados
     */
    public function getResumenArchivos()
    {
        if (!$this->archivos_generados) {
            return 'Sin archivos generados';
        }

        $tipos = collect($this->archivos_generados)->pluck('tipo')->implode(', ');
        return "Archivos generados: {$tipos}";
    }
}
