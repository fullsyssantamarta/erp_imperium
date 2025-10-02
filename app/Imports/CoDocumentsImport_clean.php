<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class CoDocumentsImport implements ToCollection, WithHeadingRow, WithValidation
{
    use Importable;
    
    private $importId;
    private $totalRows = 0;
    private $processedRows = 0;
    private $errors = [];

    public function __construct($importId = null)
    {
        $this->importId = $importId ?: uniqid('import_');
        
        // Inicializar progreso
        Cache::put("import_progress_{$this->importId}", [
            'status' => 'iniciando',
            'total' => 0,
            'processed' => 0,
            'errors' => [],
            'logs' => ['Iniciando importacion...']
        ], 3600); // 1 hora
    }

    public function collection(Collection $collection)
    {
        $this->totalRows = $collection->count();
        
        // Actualizar total
        $this->updateProgress('preparando', $this->totalRows, 0, [
            'Total de registros a procesar: ' . $this->totalRows,
            'Analizando estructura del archivo...'
        ]);

        // Debug: Mostrar estructura de la primera fila
        if ($collection->isNotEmpty()) {
            $firstRow = $collection->first();
            $headers = is_array($firstRow) ? array_keys($firstRow) : 'No es array';
            $this->updateProgress('preparando', $this->totalRows, 0, [
                'Headers encontrados: ' . json_encode($headers),
                'Primera fila: ' . json_encode($firstRow)
            ]);
        }

        foreach ($collection as $index => $row) {
            $this->processedRows++;
            
            try {
                $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                    "Procesando fila {$this->processedRows}/{$this->totalRows}",
                    "Datos de fila: " . json_encode($row),
                    "GUARDANDO en base de datos..."
                ]);

                // Usar el endpoint HTTP interno en lugar del controlador directo
                $data = $this->mapRowToRequest($row);
                
                $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                    "Datos mapeados: " . json_encode($data)
                ]);

                try {
                    // Llamar al endpoint REST interno usando Guzzle
                    $client = new \GuzzleHttp\Client();
                    $response = $client->post(url('/tenant/documents'), [
                        'headers' => [
                            'X-Requested-With' => 'XMLHttpRequest',
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . Auth::user()->api_token,
                        ],
                        'json' => $data
                    ]);

                    // Verificar resultado
                    if ($response->getStatusCode() == 200) {
                        $result = json_decode($response->getBody()->getContents(), true);
                        if (isset($result['success']) && $result['success']) {
                            $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                                "Guardado exitoso: " . ($this->getFieldValue($row, 'numero_factura', 6, 'N/A'))
                            ]);
                        } else {
                            $errorMsg = $result['message'] ?? 'Error desconocido';
                            $this->errors[] = "Fila {$this->processedRows}: {$errorMsg}";
                            $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                                "Error en fila {$this->processedRows}: {$errorMsg}"
                            ]);
                        }
                    } else {
                        $errorMsg = "HTTP Error: " . $response->getStatusCode();
                        $this->errors[] = "Fila {$this->processedRows}: {$errorMsg}";
                        $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                            "Error HTTP en fila {$this->processedRows}: {$errorMsg}"
                        ]);
                    }
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    $errorMsg = "Error de conexion: " . $e->getMessage();
                    $this->errors[] = "Fila {$this->processedRows}: {$errorMsg}";
                    $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                        "Error de conexion en fila {$this->processedRows}: {$errorMsg}"
                    ]);
                }

            } catch (\Exception $e) {
                $this->errors[] = "Fila {$this->processedRows}: " . $e->getMessage();
                $this->updateProgress('procesando', $this->totalRows, $this->processedRows, [
                    "Excepcion en fila {$this->processedRows}: " . $e->getMessage()
                ]);
            }
        }

        // Finalizar
        $status = empty($this->errors) ? 'completado' : 'completado_con_errores';
        $this->updateProgress($status, $this->totalRows, $this->processedRows, [
            "Importacion finalizada",
            "Procesados: {$this->processedRows}",
            "Errores: " . count($this->errors)
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

        // Mantener solo los ultimos 50 logs
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
                'identity_document_type_id' => $this->getDocumentTypeId($this->getFieldValue($row, 'tipo_documento_cliente', 0)),
                'number' => $this->getFieldValue($row, 'numero_documento_cliente', 1, '00000000'),
                'name' => $this->getFieldValue($row, 'nombre_cliente', 2, 'Cliente generico'),
                'email' => $this->getFieldValue($row, 'email_cliente', 3, ''),
                'telephone' => $this->getFieldValue($row, 'telefono_cliente', 4, ''),
                'address' => $this->getFieldValue($row, 'direccion_cliente', 5, 'Direccion no especificada'),
            ],
            'number' => $this->getFieldValue($row, 'numero_factura', 6, 'F-' . time()),
            'date_of_issue' => $this->getFieldValue($row, 'fecha_emision', 7, now()->format('Y-m-d')),
            'time_of_issue' => $this->getFieldValue($row, 'hora_emision', 8, now()->format('H:i:s')),
            'items' => $this->mapItems($row),
            'payments' => [
                [
                    'payment_method_type_id' => 1,
                    'reference' => $this->getFieldValue($row, 'referencia_pago', 9, null),
                ]
            ]
        ];
    }

    private function getFieldValue($row, $key, $index = null, $default = '')
    {
        if (is_array($row)) {
            if (isset($row[$key])) {
                return $row[$key];
            } elseif ($index !== null && isset($row[$index])) {
                return $row[$index];
            }
        }
        return $default;
    }

    private function getDocumentTypeId($type)
    {
        $types = [
            'CC' => 3,   // Cedula de ciudadania
            'NIT' => 6,  // NIT
            'CE' => 4,   // Cedula de extranjeria
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
                'quantity' => $this->getFieldValue($row, 'cantidad', 10, 1),
                'unit_price' => $this->getFieldValue($row, 'precio_unitario', 11, 0),
                'total' => $this->getFieldValue($row, 'total_item', 12, 0),
            ]
        ];
    }

    public function rules(): array
    {
        return [
            // Hacer las validaciones mas flexibles para debug
        ];
    }

    public function customValidationMessages()
    {
        return [
        ];
    }

    public function getImportId()
    {
        return $this->importId;
    }

    public function setImportId($importId)
    {
        $this->importId = $importId;
        
        // Reinicializar progreso con el nuevo ID
        Cache::put("import_progress_{$this->importId}", [
            'status' => 'iniciando',
            'total' => 0,
            'processed' => 0,
            'errors' => [],
            'logs' => ['Iniciando importacion...']
        ], 3600);
    }

    public function getData()
    {
        return [
            'total' => $this->totalRows,
            'registered' => $this->processedRows, // El controlador espera 'registered'
            'processed' => $this->processedRows,
            'errors' => count($this->errors),
            'error_details' => $this->errors
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
