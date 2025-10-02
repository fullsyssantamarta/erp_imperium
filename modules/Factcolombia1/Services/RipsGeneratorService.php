<?php

namespace Modules\Factcolombia1\Services;

use Modules\Factcolombia1\Models\Tenant\Document;
use Modules\Factcolombia1\Models\Tenant\RipsCtControl;
use Modules\Factcolombia1\Models\Tenant\RipsAfTransacciones;
use Modules\Factcolombia1\Models\Tenant\RipsUsUsuarios;
use Modules\Factcolombia1\Models\Tenant\RipsAcConsultas;
use Modules\Factcolombia1\Models\Tenant\RipsApProcedimientos;
use Modules\Factcolombia1\Models\Tenant\RipsAuUrgencias;
use Modules\Factcolombia1\Models\Tenant\RipsAhHospitalizacion;
use Modules\Factcolombia1\Models\Tenant\RipsAnRecienNacidos;
use Modules\Factcolombia1\Models\Tenant\RipsAmMedicamentos;
use Modules\Factcolombia1\Models\Tenant\RipsAtOtrosServicios;
use Modules\Factcolombia1\Models\Tenant\RipsGenerationControl;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Servicio para generación de archivos RIPS
 * Cumple con Resolución 3374 de 2000 y Lineamientos Técnicos 2019
 */
class RipsGeneratorService
{
    private $codigoPrestador;
    private $razonSocialPrestador;
    private $numeroRemision;
    private $fechaRemision;

    public function __construct()
    {
        $this->codigoPrestador = config('tenant.codigo_prestador', '123456789');
        $this->razonSocialPrestador = config('tenant.razon_social_prestador', 'PRESTADOR DE SALUD');
        $this->fechaRemision = Carbon::now()->format('d/m/Y');
        $this->numeroRemision = $this->generarNumeroRemision();
    }

    /**
     * Generar RIPS completo para un documento
     */
    public function generarRipsCompleto(Document $document, array $datosAdicionales = [])
    {
        try {
            Log::info("Iniciando generación RIPS para documento ID: {$document->id}");

            // 1. Crear registro de control
            $controlRips = $this->crearControlGeneracion($document);

            // 2. Generar datos RIPS según el tipo de atención
            $datosRips = $this->prepararDatosRips($document, $datosAdicionales);

            // 3. Generar archivos TXT
            $archivosTxt = $this->generarArchivosTxt($datosRips);

            // 4. Generar archivo Excel (opcional)
            $archivoExcel = $this->generarArchivoExcel($datosRips);

            // 5. Actualizar control
            $controlRips->update([
                'estado' => 'generado',
                'archivos_generados' => $archivosTxt,
                'observaciones' => 'RIPS generado exitosamente'
            ]);

            Log::info("RIPS generado exitosamente para documento ID: {$document->id}");

            return [
                'success' => true,
                'control_id' => $controlRips->id,
                'archivos_txt' => $archivosTxt,
                'archivo_excel' => $archivoExcel,
                'numero_remision' => $this->numeroRemision
            ];

        } catch (Exception $e) {
            Log::error("Error generando RIPS: " . $e->getMessage());
            
            if (isset($controlRips)) {
                $controlRips->update([
                    'estado' => 'rechazado',
                    'errores_validacion' => $e->getMessage()
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear registro de control de generación
     */
    private function crearControlGeneracion(Document $document)
    {
        return RipsGenerationControl::create([
            'document_id' => $document->id,
            'numero_remision' => $this->numeroRemision,
            'codigo_prestador' => $this->codigoPrestador,
            'fecha_generacion' => $this->fechaRemision,
            'estado' => 'pendiente'
        ]);
    }

    /**
     * Preparar datos RIPS basados en el documento
     */
    private function prepararDatosRips(Document $document, array $datosAdicionales)
    {
        $datos = [
            'document' => $document,
            'customer' => $document->customer,
            'items' => $document->items,
            'health_fields' => $document->health_fields ?? [],
            'health_users' => $document->health_users ?? [],
            'datos_adicionales' => $datosAdicionales
        ];

        // Generar datos por tipo de archivo RIPS
        return [
            'ct' => $this->generarDatosCT($datos),
            'af' => $this->generarDatosAF($datos),
            'us' => $this->generarDatosUS($datos),
            'ac' => $this->generarDatosAC($datos),
            'ap' => $this->generarDatosAP($datos),
            'au' => $this->generarDatosAU($datos),
            'ah' => $this->generarDatosAH($datos),
            'an' => $this->generarDatosAN($datos),
            'am' => $this->generarDatosAM($datos),
            'at' => $this->generarDatosAT($datos)
        ];
    }

    /**
     * Generar datos para archivo CT (Control)
     */
    private function generarDatosCT($datos)
    {
        $document = $datos['document'];
        
        $ct = RipsCtControl::create([
            'codigo_prestador' => $this->codigoPrestador,
            'fecha_remision' => $this->fechaRemision,
            'total_registros_enviados' => 1, // Se calculará según archivos generados
            'total_valor_enviado' => $document->total,
            'numero_remision' => $this->numeroRemision
        ]);

        return $ct;
    }

    /**
     * Generar datos para archivo AF (Transacciones)
     */
    private function generarDatosAF($datos)
    {
        $document = $datos['document'];
        $customer = $datos['customer'];
        
        $af = RipsAfTransacciones::create([
            'document_id' => $document->id,
            'codigo_prestador' => $this->codigoPrestador,
            'razon_social_prestador' => $this->razonSocialPrestador,
            'tipo_identificacion_prestador' => '31', // NIT
            'numero_identificacion_prestador' => $this->codigoPrestador,
            'numero_factura' => $document->number,
            'fecha_expedicion_factura' => Carbon::parse($document->date_issue)->format('d/m/Y'),
            'fecha_inicio_periodo_facturado' => Carbon::parse($document->date_issue)->format('d/m/Y'),
            'fecha_final_periodo_facturado' => Carbon::parse($document->date_issue)->format('d/m/Y'),
            'codigo_entidad_administradora' => $datos['datos_adicionales']['codigo_eapb'] ?? 'EAPB01',
            'nombre_entidad_administradora' => $datos['datos_adicionales']['nombre_eapb'] ?? 'ENTIDAD ADMINISTRADORA',
            'numero_contrato' => $datos['datos_adicionales']['numero_contrato'] ?? '001',
            'plan_beneficios' => $datos['datos_adicionales']['plan_beneficios'] ?? '01',
            'numero_poliza' => $datos['datos_adicionales']['numero_poliza'] ?? null,
            'valor_total_pagado_entidad' => $document->total,
            'valor_comision_entidad' => 0,
            'valor_descuentos_entidad' => $document->total_discount ?? 0,
            'valor_neto_pagado_entidad' => $document->total
        ]);

        return $af;
    }

    /**
     * Generar datos para archivo US (Usuarios)
     */
    private function generarDatosUS($datos)
    {
        $document = $datos['document'];
        $customer = $datos['customer'];
        $healthUsers = $datos['health_users'];

        $usuarios = [];

        // Si hay usuarios de salud específicos, usarlos
        if (!empty($healthUsers)) {
            foreach ($healthUsers as $healthUser) {
                $usuarios[] = $this->crearUsuarioRips($document, $healthUser);
            }
        } else {
            // Crear usuario basado en el cliente
            $usuarios[] = $this->crearUsuarioRipsDesdeCliente($document, $customer);
        }

        return $usuarios;
    }

    /**
     * Crear usuario RIPS desde datos de health_users
     */
    private function crearUsuarioRips($document, $healthUser)
    {
        // Implementar lógica para crear usuario RIPS
        // Basado en los datos de health_users del formulario
        return [
            'document_id' => $document->id,
            'tipo_identificacion_usuario' => $healthUser['tipo_identificacion'] ?? 'CC',
            'numero_identificacion_usuario' => $healthUser['identification_number'],
            'codigo_entidad_administradora' => $healthUser['codigo_eapb'] ?? 'EAPB01',
            'tipo_usuario' => $healthUser['tipo_usuario'] ?? '1',
            'primer_apellido' => strtoupper($healthUser['surname']),
            'segundo_apellido' => strtoupper($healthUser['second_surname'] ?? ''),
            'primer_nombre' => strtoupper($healthUser['first_name']),
            'segundo_nombre' => strtoupper($healthUser['middle_name'] ?? ''),
            'edad' => $this->calcularEdad($healthUser['fecha_nacimiento'] ?? '1990-01-01'),
            'unidad_medida_edad' => '1', // Años
            'sexo' => $healthUser['sexo'] ?? 'M',
            'codigo_departamento' => $healthUser['codigo_departamento'] ?? '05',
            'codigo_municipio' => $healthUser['codigo_municipio'] ?? '001',
            'zona_residencia' => 'U'
        ];
    }

    /**
     * Generar datos para archivo AC (Consultas)
     */
    private function generarDatosAC($datos)
    {
        $document = $datos['document'];
        $items = $datos['items'];
        
        $consultas = [];

        foreach ($items as $item) {
            // Solo incluir items que sean consultas (según código CUPS)
            if ($this->esConsulta($item)) {
                $consultas[] = [
                    'document_id' => $document->id,
                    'numero_factura' => $document->number,
                    'codigo_prestador' => $this->codigoPrestador,
                    'tipo_identificacion_usuario' => 'CC',
                    'numero_identificacion_usuario' => $document->customer->number,
                    'fecha_consulta' => Carbon::parse($document->date_issue)->format('d/m/Y'),
                    'numero_autorizacion' => $datos['datos_adicionales']['numero_autorizacion'] ?? '',
                    'codigo_consulta' => $this->extraerCodigoCUPS($item),
                    'modalidad_grupo_servicio_terapeutico' => '01',
                    'grupo_servicios' => '01',
                    'servicios_solicitados' => '001',
                    'diagnostico_principal' => $datos['datos_adicionales']['diagnostico_principal'] ?? 'Z000',
                    'diagnostico_relacionado1' => $datos['datos_adicionales']['diagnostico_relacionado1'] ?? null,
                    'diagnostico_relacionado2' => $datos['datos_adicionales']['diagnostico_relacionado2'] ?? null,
                    'diagnostico_relacionado3' => $datos['datos_adicionales']['diagnostico_relacionado3'] ?? null,
                    'tipo_diagnostico_principal' => '1',
                    'valor_consulta' => $item->total,
                    'valor_cuota_moderadora' => $datos['datos_adicionales']['cuota_moderadora'] ?? 0,
                    'valor_neto_pagar' => $item->total
                ];
            }
        }

        return $consultas;
    }

    /**
     * Generar archivos TXT
     */
    private function generarArchivosTxt($datosRips)
    {
        $archivos = [];
        $basePath = "rips/" . $this->numeroRemision;

        // Crear directorio si no existe
        Storage::disk('public')->makeDirectory($basePath);

        // Generar cada archivo RIPS
        $archivosGenerar = [
            'CT' => $datosRips['ct'],
            'AF' => $datosRips['af'],
            'US' => $datosRips['us'],
            'AC' => $datosRips['ac'],
            'AP' => $datosRips['ap'],
            'AU' => $datosRips['au'],
            'AH' => $datosRips['ah'],
            'AN' => $datosRips['an'],
            'AM' => $datosRips['am'],
            'AT' => $datosRips['at']
        ];

        foreach ($archivosGenerar as $tipo => $datos) {
            if (!empty($datos)) {
                $nombreArchivo = $tipo . $this->codigoPrestador . ".txt";
                $rutaArchivo = $basePath . "/" . $nombreArchivo;
                
                $contenido = $this->generarContenidoTxt($tipo, $datos);
                Storage::disk('public')->put($rutaArchivo, $contenido);
                
                $archivos[] = [
                    'tipo' => $tipo,
                    'nombre' => $nombreArchivo,
                    'ruta' => $rutaArchivo,
                    'url' => Storage::disk('public')->url($rutaArchivo)
                ];
            }
        }

        return $archivos;
    }

    /**
     * Generar contenido TXT para cada tipo de archivo
     */
    private function generarContenidoTxt($tipo, $datos)
    {
        $lineas = [];

        switch ($tipo) {
            case 'CT':
                if ($datos) {
                    $lineas[] = $datos->generarContenidoCT();
                }
                break;
                
            case 'AF':
                if ($datos) {
                    $lineas[] = $datos->generarLineaAF();
                }
                break;
                
            case 'US':
                foreach ($datos as $usuario) {
                    $lineas[] = $this->generarLineaUS($usuario);
                }
                break;
                
            case 'AC':
                foreach ($datos as $consulta) {
                    $lineas[] = $this->generarLineaAC($consulta);
                }
                break;
                
            // Implementar otros tipos según necesidad
        }

        return implode("\n", $lineas);
    }

    /**
     * Generar línea US (Usuario)
     */
    private function generarLineaUS($usuario)
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s,%s",
            $usuario['tipo_identificacion_usuario'],
            $usuario['numero_identificacion_usuario'],
            $usuario['codigo_entidad_administradora'],
            $usuario['tipo_usuario'],
            $usuario['primer_apellido'],
            $usuario['segundo_apellido'],
            $usuario['primer_nombre'],
            $usuario['segundo_nombre'],
            $usuario['edad'],
            $usuario['unidad_medida_edad'],
            $usuario['sexo'],
            $usuario['codigo_departamento'],
            $usuario['codigo_municipio'],
            $usuario['zona_residencia']
        );
    }

    /**
     * Generar línea AC (Consulta)
     */
    private function generarLineaAC($consulta)
    {
        return sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f",
            $consulta['numero_factura'],
            $consulta['codigo_prestador'],
            $consulta['tipo_identificacion_usuario'],
            $consulta['numero_identificacion_usuario'],
            $consulta['fecha_consulta'],
            $consulta['numero_autorizacion'],
            $consulta['codigo_consulta'],
            $consulta['modalidad_grupo_servicio_terapeutico'],
            $consulta['grupo_servicios'],
            $consulta['servicios_solicitados'],
            $consulta['diagnostico_principal'],
            $consulta['diagnostico_relacionado1'] ?? '',
            $consulta['diagnostico_relacionado2'] ?? '',
            $consulta['diagnostico_relacionado3'] ?? '',
            $consulta['tipo_diagnostico_principal'],
            $consulta['valor_consulta'],
            $consulta['valor_cuota_moderadora'],
            $consulta['valor_neto_pagar']
        );
    }

    /**
     * Generar archivo Excel con pestañas
     */
    private function generarArchivoExcel($datosRips)
    {
        $spreadsheet = new Spreadsheet();
        
        // Crear pestaña para cada tipo de archivo RIPS
        $tipos = ['CT', 'AF', 'US', 'AC', 'AP', 'AU', 'AH', 'AN', 'AM', 'AT'];
        $firstSheet = true;

        foreach ($tipos as $tipo) {
            if (!empty($datosRips[strtolower($tipo)])) {
                if ($firstSheet) {
                    $sheet = $spreadsheet->getActiveSheet();
                    $firstSheet = false;
                } else {
                    $sheet = $spreadsheet->createSheet();
                }
                
                $sheet->setTitle($tipo);
                $this->llenarHojaExcel($sheet, $tipo, $datosRips[strtolower($tipo)]);
            }
        }

        // Guardar archivo
        $nombreArchivo = "RIPS_" . $this->numeroRemision . ".xlsx";
        $rutaArchivo = "rips/" . $this->numeroRemision . "/" . $nombreArchivo;
        
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'rips_excel');
        $writer->save($tempFile);
        
        Storage::disk('public')->put($rutaArchivo, file_get_contents($tempFile));
        unlink($tempFile);

        return [
            'nombre' => $nombreArchivo,
            'ruta' => $rutaArchivo,
            'url' => Storage::disk('public')->url($rutaArchivo)
        ];
    }

    /**
     * Métodos auxiliares
     */
    private function generarNumeroRemision()
    {
        return 'R' . Carbon::now()->format('YmdHis');
    }

    private function calcularEdad($fechaNacimiento)
    {
        return Carbon::parse($fechaNacimiento)->age;
    }

    private function esConsulta($item)
    {
        // Implementar lógica para determinar si un item es una consulta
        // Basado en código CUPS o categoría del item
        return true; // Simplificado por ahora
    }

    private function extraerCodigoCUPS($item)
    {
        // Extraer código CUPS del item
        return $item->internal_id ?? '9001'; // Código por defecto
    }

    // Implementar métodos adicionales para otros tipos de archivos RIPS
    private function generarDatosAP($datos) { return []; }
    private function generarDatosAU($datos) { return []; }
    private function generarDatosAH($datos) { return []; }
    private function generarDatosAN($datos) { return []; }
    private function generarDatosAM($datos) { return []; }
    private function generarDatosAT($datos) { return []; }

    private function crearUsuarioRipsDesdeCliente($document, $customer)
    {
        return [
            'document_id' => $document->id,
            'tipo_identificacion_usuario' => $customer->identity_document_type_id == 6 ? 'CC' : 'CE',
            'numero_identificacion_usuario' => $customer->number,
            'codigo_entidad_administradora' => 'EAPB01',
            'tipo_usuario' => '1',
            'primer_apellido' => strtoupper(explode(' ', $customer->name)[0] ?? ''),
            'segundo_apellido' => '',
            'primer_nombre' => strtoupper(explode(' ', $customer->name)[1] ?? $customer->name),
            'segundo_nombre' => '',
            'edad' => 30, // Por defecto
            'unidad_medida_edad' => '1',
            'sexo' => 'M',
            'codigo_departamento' => '05',
            'codigo_municipio' => '001',
            'zona_residencia' => 'U'
        ];
    }

    private function llenarHojaExcel($sheet, $tipo, $datos)
    {
        // Implementar llenado de hoja Excel según el tipo
        // Por ahora una implementación básica
        $sheet->setCellValue('A1', 'Tipo: ' . $tipo);
        $sheet->setCellValue('A2', 'Datos: ' . json_encode($datos));
    }
}
