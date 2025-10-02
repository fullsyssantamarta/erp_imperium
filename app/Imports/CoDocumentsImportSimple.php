<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Modules\Factcolombia1\Http\Requests\Tenant\DocumentRequest;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Illuminate\Support\Facades\Cache;

class CoDocumentsImportSimple implements ToCollection, WithHeadingRow, WithValidation
{
    private $importId;
    private $totalRows = 0;
    private $processedRows = 0;
    private $errors = [];
    private $documentController;

    public function __construct($importId = null)
    {
        $this->importId = $importId ?: uniqid('import_');
        $this->documentController = new DocumentController();
        
        // Inicializar progreso
        Cache::put("import_progress_{$this->importId}", [
            'status' => 'iniciando',
            'total' => 0,
            'processed' => 0,
            'errors' => [],
            'logs' => ['📋 Iniciando importación...']
        ], 3600); // 1 hora
    }

    public function collection(Collection $collection)
    {
        $this->totalRows = $collection->count();
        
        // Actualizar total
        $this->updateProgress('preparando', $this->totalRows, 0, ['📊 Total de registros a procesar: ' . $this->totalRows]);

        foreach ($collection as $index => $row) {
            $this->processedRows++;
            
            try {
                $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                    "📄 Procesando fila {$this->processedRows}/{$this->totalRows}",
                    "💾 GUARDANDO en base de datos: " . ($row['numero_factura'] ?? 'N/A')
                ]);

                // Crear el request
                $request = new DocumentRequest();
                $request->merge($this->mapRowToRequest($row));

                // Llamar al store del controlador - devuelve array, no Response
                $result = $this->documentController->store($request, null);

                // Verificar resultado - es un array
                if (is_array($result) && isset($result['success']) && $result['success']) {
                    $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                        "✅ Guardado exitoso: " . ($row['numero_factura'] ?? 'N/A')
                    ]);
                } else {
                    $errorMsg = is_array($result) ? ($result['message'] ?? 'Error desconocido') : 'Respuesta inválida';
                    $this->errors[] = "Fila {$this->processedRows}: {$errorMsg}";
                    $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                        "❌ Error en fila {$this->processedRows}: {$errorMsg}"
                    ]);
                }

            } catch (\Exception $e) {
                $this->errors[] = "Fila {$this->processedRows}: " . $e->getMessage();
                $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                    "❌ Excepción en fila {$this->processedRows}: " . $e->getMessage()
                ]);
            }
        }

        // Finalizar
        $status = empty($this->errors) ? 'completado' : 'completado_con_errores';
        $this->updateProgress($status, $this->totalRows, $this->processedRows, [
            "🎉 Importación finalizada",
            "✅ Procesados: {$this->processedRows}",
            "❌ Errores: " . count($this->errors)
        ]);
    }

    private function updateProgress($status, $total, $processed, $newLogs = [])
    {
        $progress = Cache::get("import_progress_{$this->importId}", [
            'status' => 'iniciando',
            'total' => 0,
            'processed' => 0,
            'errors' => [],
            'logs' => []
        ]);

        $progress['status'] = $status;
        $progress['total'] = $total;
        $progress['processed'] = $processed;
        $progress['errors'] = $this->errors;

        // Agregar nuevos logs
        foreach ($newLogs as $log) {
            $progress['logs'][] = '[' . date('H:i:s') . '] ' . $log;
        }

        // Mantener solo los últimos 50 logs
        if (count($progress['logs']) > 50) {
            $progress['logs'] = array_slice($progress['logs'], -50);
        }

        Cache::put("import_progress_{$this->importId}", $progress, 3600);
    }

    private function mapRowToRequest($row)
    {
        return [
            'type_document_id' => 1, // Factura
            'customer' => [
                'identity_document_type_id' => $this->getDocumentTypeId($row['tipo_documento_cliente'] ?? ''),
                'number' => $row['numero_documento_cliente'] ?? '',
                'name' => $row['nombre_cliente'] ?? '',
                'email' => $row['email_cliente'] ?? '',
                'telephone' => $row['telefono_cliente'] ?? '',
                'address' => $row['direccion_cliente'] ?? '',
            ],
            'number' => $row['numero_factura'] ?? '',
            'date_of_issue' => $row['fecha_emision'] ?? now()->format('Y-m-d'),
            'time_of_issue' => $row['hora_emision'] ?? now()->format('H:i:s'),
            'items' => $this->mapItems($row),
            'payments' => [
                [
                    'payment_method_type_id' => 1,
                    'reference' => $row['referencia_pago'] ?? null,
                ]
            ]
        ];
    }

    private function getDocumentTypeId($type)
    {
        $types = [
            'CC' => 3,   // Cédula de ciudadanía
            'NIT' => 6,  // NIT
            'CE' => 4,   // Cédula de extranjería
            'TI' => 7,   // Tarjeta de identidad
            'PP' => 8,   // Pasaporte
        ];
        
        return $types[strtoupper($type)] ?? 3; // Por defecto CC
    }

    private function mapItems($row)
    {
        return [
            [
                'item_id' => 1, // Item por defecto
                'quantity' => $row['cantidad'] ?? 1,
                'unit_price' => $row['precio_unitario'] ?? 0,
                'total' => $row['total_item'] ?? ($row['cantidad'] ?? 1) * ($row['precio_unitario'] ?? 0),
            ]
        ];
    }

    public function rules(): array
    {
        return [
            'numero_factura' => 'required',
            'nombre_cliente' => 'required',
            'numero_documento_cliente' => 'required',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'numero_factura.required' => 'El número de factura es obligatorio',
            'nombre_cliente.required' => 'El nombre del cliente es obligatorio',
            'numero_documento_cliente.required' => 'El número de documento del cliente es obligatorio',
        ];
    }

    public function getImportId()
    {
        return $this->importId;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
