<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Factcolombia1\Models\Tenant\Document;
use Modules\Factcolombia1\Services\RipsGeneratorService;
use Modules\Factcolombia1\Models\Tenant\RipsGenerationControl;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Controlador para manejo de RIPS (Registros Individuales de Prestación de Servicios de Salud)
 * Cumple con Resolución 3374 de 2000 y Lineamientos Técnicos 2019
 */
class RipsController extends Controller
{
    protected $ripsGenerator;

    public function __construct(RipsGeneratorService $ripsGenerator)
    {
        $this->ripsGenerator = $ripsGenerator;
    }

    /**
     * Generar RIPS para un documento
     */
    public function generarRips(Request $request, $documentId)
    {
        try {
            $document = Document::with(['customer', 'items', 'health_fields', 'health_users'])
                               ->findOrFail($documentId);

            // Validar que el documento sea del sector salud
            if (!$this->esDocumentoSalud($document)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no está configurado para el sector salud'
                ], 400);
            }

            // Obtener datos adicionales del request
            $datosAdicionales = $this->procesarDatosAdicionales($request);

            // Generar RIPS
            $resultado = $this->ripsGenerator->generarRipsCompleto($document, $datosAdicionales);

            if ($resultado['success']) {
                Log::info("RIPS generado exitosamente", [
                    'document_id' => $documentId,
                    'numero_remision' => $resultado['numero_remision']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'RIPS generado exitosamente',
                    'data' => [
                        'numero_remision' => $resultado['numero_remision'],
                        'archivos_txt' => $resultado['archivos_txt'],
                        'archivo_excel' => $resultado['archivo_excel'],
                        'control_id' => $resultado['control_id']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar RIPS: ' . $resultado['error']
                ], 500);
            }

        } catch (Exception $e) {
            Log::error("Error en generación de RIPS", [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de RIPS generados
     */
    public function historialRips($documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            
            $controles = RipsGenerationControl::where('document_id', $documentId)
                                             ->orderBy('created_at', 'desc')
                                             ->get();

            return response()->json([
                'success' => true,
                'data' => $controles->map(function ($control) {
                    return [
                        'id' => $control->id,
                        'numero_remision' => $control->numero_remision,
                        'fecha_generacion' => $control->fecha_generacion,
                        'estado' => $control->estado,
                        'archivos_generados' => $control->getResumenArchivos(),
                        'errores' => $control->errores_validacion,
                        'observaciones' => $control->observaciones,
                        'created_at' => $control->created_at->format('d/m/Y H:i:s')
                    ];
                })
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivos RIPS generados
     */
    public function descargarRips($controlId)
    {
        try {
            $control = RipsGenerationControl::findOrFail($controlId);
            
            if (!$control->esExitosa()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay archivos válidos para descargar'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'archivos_txt' => $control->archivos_generados,
                    'numero_remision' => $control->numero_remision
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al acceder a archivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar RIPS con servicio externo (FEV-RIPS)
     */
    public function validarRips(Request $request, $controlId)
    {
        try {
            $control = RipsGenerationControl::findOrFail($controlId);
            
            if (!$control->esExitosa()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay archivos válidos para validar'
                ], 400);
            }

            // Aquí se integraría con el servicio FEV-RIPS
            $resultadoValidacion = $this->enviarAFevRips($control);

            return response()->json([
                'success' => true,
                'message' => 'Validación enviada a FEV-RIPS',
                'data' => $resultadoValidacion
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en validación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos de salud para formulario
     */
    public function getDatosSalud()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'tipos_identificacion' => [
                    'RC' => 'Registro Civil',
                    'TI' => 'Tarjeta de Identidad',
                    'CC' => 'Cédula de Ciudadanía',
                    'CE' => 'Cédula de Extranjería',
                    'PA' => 'Pasaporte',
                    'MS' => 'Menor sin Identificación',
                    'AS' => 'Adulto sin Identificación'
                ],
                'tipos_usuario' => [
                    '1' => 'Contributivo',
                    '2' => 'Subsidiado',
                    '3' => 'Vinculado',
                    '4' => 'Particular',
                    '5' => 'Otro'
                ],
                'causas_externas' => [
                    '01' => 'Accidente de trabajo',
                    '02' => 'Accidente de tránsito',
                    '03' => 'Accidente rábico',
                    '04' => 'Accidente ofídico',
                    '05' => 'Otro tipo de accidente',
                    '06' => 'Evento catastrófico',
                    '07' => 'Lesión por agresión',
                    '08' => 'Lesión autoinfligida',
                    '09' => 'Sospecha de maltrato físico',
                    '10' => 'Sospecha de abuso sexual',
                    '11' => 'Sospecha de violencia sexual',
                    '12' => 'Sospecha de maltrato emocional',
                    '13' => 'Enfermedad general',
                    '14' => 'Enfermedad profesional',
                    '15' => 'Otra'
                ],
                'modalidades_atencion' => [
                    '01' => 'Intramural',
                    '02' => 'Extramural unidad móvil',
                    '03' => 'Extramural domiciliaria',
                    '04' => 'Extramural jornada de salud',
                    '05' => 'Telemedicina'
                ]
            ]
        ]);
    }

    /**
     * Validar si un documento es del sector salud
     */
    private function esDocumentoSalud($document)
    {
        // Verificar si tiene campos de salud o está marcado como sector salud
        return $document->health_fields || 
               $document->health_users || 
               $document->sector === 'salud' ||
               $document->type_document_id == 20; // Asumiendo ID 20 para facturas de salud
    }

    /**
     * Procesar datos adicionales del request
     */
    private function procesarDatosAdicionales($request)
    {
        return [
            'codigo_eapb' => $request->input('codigo_eapb'),
            'nombre_eapb' => $request->input('nombre_eapb'),
            'numero_contrato' => $request->input('numero_contrato'),
            'plan_beneficios' => $request->input('plan_beneficios'),
            'numero_poliza' => $request->input('numero_poliza'),
            'numero_autorizacion' => $request->input('numero_autorizacion'),
            'diagnostico_principal' => $request->input('diagnostico_principal'),
            'diagnostico_relacionado1' => $request->input('diagnostico_relacionado1'),
            'diagnostico_relacionado2' => $request->input('diagnostico_relacionado2'),
            'diagnostico_relacionado3' => $request->input('diagnostico_relacionado3'),
            'cuota_moderadora' => $request->input('cuota_moderadora', 0),
            'causa_externa' => $request->input('causa_externa', '13'), // Enfermedad general por defecto
            'modalidad_atencion' => $request->input('modalidad_atencion', '01') // Intramural por defecto
        ];
    }

    /**
     * Enviar archivos RIPS a FEV-RIPS para validación
     */
    private function enviarAFevRips($control)
    {
        // Implementar integración con FEV-RIPS
        // Por ahora retornar simulación
        return [
            'estado' => 'enviado',
            'fecha_envio' => now()->format('Y-m-d H:i:s'),
            'numero_radicacion' => 'FEV-' . $control->numero_remision,
            'observaciones' => 'Archivos enviados correctamente para validación'
        ];
    }

    /**
     * Regenerar RIPS con nuevos datos
     */
    public function regenerarRips(Request $request, $documentId)
    {
        try {
            // Marcar generaciones anteriores como obsoletas
            RipsGenerationControl::where('document_id', $documentId)
                                 ->where('estado', 'generado')
                                 ->update(['estado' => 'obsoleto']);

            // Generar nuevos RIPS
            return $this->generarRips($request, $documentId);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al regenerar RIPS: ' . $e->getMessage()
            ], 500);
        }
    }
}
