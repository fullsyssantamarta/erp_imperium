<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Imports\CoDocumentsHealthImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

/**
 * Controlador para importación masiva de facturas del sector salud
 * Maneja archivos del formato DDB_DOCUMENTO_CAMPOS_DQ
 */
class HealthInvoiceImportController extends Controller
{
    /**
     * Mostrar la vista para importar facturas del sector salud
     */
    public function index()
    {
        return view('tenant.health_invoice_import.index');
    }

    /**
     * Procesar la importación del archivo Excel del sector salud
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Máximo 10MB
        ]);

        try {
            $file = $request->file('file');
            $import = new CoDocumentsHealthImport();
            
            // Ejecutar la importación
            Excel::import($import, $file);
            
            // Obtener resultados
            $data = $import->getData();
            $processedInvoices = $import->getProcessedInvoices();
            $errors = $import->getErrors();
            
            $response = [
                'success' => true,
                'message' => 'Importación completada exitosamente',
                'data' => [
                    'total_invoices' => $data['total'] ?? 0,
                    'processed_invoices' => $data['registered'] ?? 0,
                    'failed_invoices' => ($data['total'] ?? 0) - ($data['registered'] ?? 0),
                    'errors_count' => count($errors),
                    'processed_details' => $processedInvoices,
                    'errors' => $errors
                ]
            ];

            if (!empty($errors)) {
                $response['warning'] = 'Importación completada con algunos errores';
            }

            return response()->json($response);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante la importación: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Descargar plantilla de Excel para importación del sector salud
     */
    public function downloadTemplate()
    {
        $templatePath = public_path('templates/DDB_DOCUMENTO_CAMPOS_SALUD_TEMPLATE.xlsx');
        
        if (!file_exists($templatePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no disponible'
            ], 404);
        }

        return response()->download($templatePath, 'Plantilla_Facturas_Sector_Salud.xlsx');
    }

    /**
     * Obtener información sobre el formato requerido
     */
    public function getFormatInfo()
    {
        return response()->json([
            'success' => true,
            'format_info' => [
                'name' => 'DDB Documento Campos DQ - Sector Salud',
                'description' => 'Formato especializado para importación masiva de facturas del sector salud en Colombia',
                'features' => [
                    'Agrupación automática de productos por factura',
                    'Creación automática de pacientes y EPS',
                    'Cálculo automático de retenciones del sector salud',
                    'Validación de campos RIPS obligatorios',
                    'Soporte para múltiples productos por factura',
                    'Manejo de diagnósticos y tecnologías en salud'
                ],
                'required_columns' => [
                    'INVOICECODE' => 'Código único de la factura',
                    'NUMDOCUMENTOIDENTIFICACION' => 'Identificación del paciente',
                    'PRIMER_NOMBRE' => 'Primer nombre del paciente',
                    'PRIMER_APELLIDO' => 'Primer apellido del paciente',
                    'EPS' => 'Código de la EPS',
                    'ACP_PARTYNAME' => 'Nombre de la EPS',
                    'CODDIAGNOSTICOPRINCIPAL' => 'Código del diagnóstico principal',
                    'CODTECNOLOGIASALUD' => 'Código de la tecnología en salud',
                    'ITEMDESCRIPTION' => 'Descripción del producto/servicio',
                    'SELLERSITEMIDENTIFICATION' => 'Código del producto',
                    'INVOICEDQUANTITY' => 'Cantidad facturada',
                    'PRODUCTVALUE' => 'Valor unitario del producto',
                    'PAYABLEAMOUNT' => 'Valor total de la factura'
                ],
                'health_specific_fields' => [
                    'CODPRESTADOR' => 'Código del prestador de servicios',
                    'NUMAUTORIZACION' => 'Número de autorización',
                    'CONCENTRACIONMEDICAMENTO' => 'Concentración del medicamento',
                    'FORMAFARMACEUTICA' => 'Forma farmacéutica',
                    'CANTIDADMEDICAMENTO' => 'Cantidad de medicamento',
                    'DIASTRATAMIENTO' => 'Días de tratamiento',
                    'COPAGO' => 'Valor del copago',
                    'CUOTA_MODERADORA' => 'Valor de la cuota moderadora'
                ],
                'validation_rules' => [
                    'Cada factura debe tener al menos un producto/servicio',
                    'Todos los pacientes deben tener diagnóstico principal',
                    'Los códigos de tecnología en salud son obligatorios',
                    'Las EPS deben existir o serán creadas automáticamente',
                    'Los productos se crearán automáticamente si no existen'
                ]
            ]
        ]);
    }

    /**
     * Validar archivo antes de la importación
     */
    public function validateFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('file');
            
            // Cargar el archivo para validación previa
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();
            
            if (empty($data) || count($data) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo está vacío o no tiene datos'
                ]);
            }

            $headers = $data[0];
            $requiredColumns = [
                'INVOICECODE', 'NUMDOCUMENTOIDENTIFICACION', 'EPS', 
                'CODDIAGNOSTICOPRINCIPAL', 'SELLERSITEMIDENTIFICATION'
            ];
            
            $missingColumns = [];
            $headerMap = array_flip($headers);
            
            foreach ($requiredColumns as $required) {
                if (!isset($headerMap[$required])) {
                    $missingColumns[] = $required;
                }
            }

            if (!empty($missingColumns)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Columnas requeridas faltantes: ' . implode(', ', $missingColumns),
                    'missing_columns' => $missingColumns
                ]);
            }

            // Análisis básico de datos
            $dataRows = array_slice($data, 1);
            $invoiceCodes = [];
            $patients = [];
            $duplicatedInvoices = 0;
            
            foreach ($dataRows as $row) {
                if (!empty($row[$headerMap['INVOICECODE']])) {
                    $invoiceCode = $row[$headerMap['INVOICECODE']];
                    if (isset($invoiceCodes[$invoiceCode])) {
                        $invoiceCodes[$invoiceCode]++;
                    } else {
                        $invoiceCodes[$invoiceCode] = 1;
                    }
                }
                
                if (!empty($row[$headerMap['NUMDOCUMENTOIDENTIFICACION']])) {
                    $patients[] = $row[$headerMap['NUMDOCUMENTOIDENTIFICACION']];
                }
            }
            
            $multiProductInvoices = array_filter($invoiceCodes, function($count) {
                return $count > 1;
            });

            return response()->json([
                'success' => true,
                'message' => 'Archivo validado correctamente',
                'validation_results' => [
                    'total_rows' => count($dataRows),
                    'total_invoices' => count($invoiceCodes),
                    'unique_patients' => count(array_unique($patients)),
                    'multi_product_invoices' => count($multiProductInvoices),
                    'estimated_processing_time' => ceil(count($invoiceCodes) / 10) . ' minutos',
                    'headers_found' => count($headers),
                    'required_headers_present' => true
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validando el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
