<?php

namespace Modules\Factcolombia1\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para Control RIPS (CT)
 * Basado en Resolución 3374 de 2000
 */
class RipsCtControl extends Model
{
    protected $table = 'rips_ct_control';
    
    protected $fillable = [
        'codigo_prestador',
        'fecha_remision',
        'total_registros_enviados',
        'total_valor_enviado',
        'numero_remision'
    ];

    protected $casts = [
        'total_valor_enviado' => 'decimal:2'
    ];

    /**
     * Generar contenido del archivo CT
     */
    public function generarContenidoCT()
    {
        return sprintf(
            "%s,%s,%d,%.2f,%s",
            $this->codigo_prestador,
            $this->fecha_remision,
            $this->total_registros_enviados,
            $this->total_valor_enviado,
            $this->numero_remision
        );
    }
}
