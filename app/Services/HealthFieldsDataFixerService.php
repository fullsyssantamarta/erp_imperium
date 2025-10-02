<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 🏗️ ENTERPRISE SERVICE: HealthFieldsDataFixer
 * 
 * Servicio robusto para corregir facturas existentes con health_fields duplicados/corruptos.
 * Diseñado para operaciones de producción con validación, backup automático y rollback.
 * 
 * Features Enterprise:
 * - ✅ Backup automático antes de cada corrección
 * - ✅ Validación rigurosa de datos
 * - ✅ Rollback capability
 * - ✅ Batch processing con progress tracking
 * - ✅ Logging detallado para auditoria
 * - ✅ Zero-downtime operation
 * 
 * Author: Senior Architect - Multi-Tenant APIDIAN 2025
 * Version: 1.0 Enterprise
 */
class HealthFieldsDataFixerService
{
    private $healthFieldsCleaner;
    private $backupTable = 'documents_health_fields_backup';
    
    public function __construct(HealthFieldsCleanerService $healthFieldsCleaner)
    {
        $this->healthFieldsCleaner = $healthFieldsCleaner;
    }

    /**
     * ENTERPRISE: Analiza el alcance del problema en la base de datos
     */
    public function analyzeCorruptionScope(): array
    {
        Log::info('🔍 ENTERPRISE ANALYSIS: Starting health_fields corruption scope analysis');

        $analysis = DB::select("
            SELECT 
                COUNT(*) as total_with_health_fields,
                SUM(CASE WHEN JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields') THEN 1 ELSE 0 END) as nested_structure_count,
                SUM(CASE WHEN JSON_EXTRACT(health_fields, '$.health_type_operation_id') != JSON_EXTRACT(health_fields, '$.health_fields.health_type_operation_id') THEN 1 ELSE 0 END) as inconsistent_ids_count,
                AVG(LENGTH(health_fields)) as avg_size_bytes,
                MIN(LENGTH(health_fields)) as min_size_bytes,
                MAX(LENGTH(health_fields)) as max_size_bytes
            FROM documents 
            WHERE health_fields IS NOT NULL AND health_fields != '' AND health_fields != '{}'
        ")[0];

        $corruptedDocuments = DB::select("
            SELECT id, number, type_document_id, date_of_issue, LENGTH(health_fields) as size_bytes
            FROM documents 
            WHERE health_fields IS NOT NULL 
                AND JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields')
            ORDER BY date_of_issue DESC
        ");

        $result = [
            'total_documents_with_health_fields' => $analysis->total_with_health_fields,
            'corrupted_documents_count' => $analysis->nested_structure_count,
            'inconsistent_ids_count' => $analysis->inconsistent_ids_count,
            'corruption_percentage' => round(($analysis->nested_structure_count / $analysis->total_with_health_fields) * 100, 2),
            'size_analysis' => [
                'average_bytes' => round($analysis->avg_size_bytes, 0),
                'min_bytes' => $analysis->min_size_bytes,
                'max_bytes' => $analysis->max_size_bytes,
                'expected_size_reduction' => '~40-50%'
            ],
            'corrupted_documents' => array_map(function($doc) {
                return [
                    'id' => $doc->id,
                    'number' => $doc->number,
                    'type' => $doc->type_document_id,
                    'date' => $doc->date_of_issue,
                    'size_bytes' => $doc->size_bytes
                ];
            }, $corruptedDocuments)
        ];

        Log::info('✅ ENTERPRISE ANALYSIS: Corruption scope analysis completed', [
            'corrupted_count' => $result['corrupted_documents_count'],
            'corruption_percentage' => $result['corruption_percentage']
        ]);

        return $result;
    }

    /**
     * ENTERPRISE: Crea backup automático de documentos antes de la corrección
     */
    public function createBackup(): string
    {
        $backupId = 'health_fields_backup_' . date('Y_m_d_H_i_s');
        
        Log::info('💾 ENTERPRISE BACKUP: Creating backup before health_fields correction', [
            'backup_id' => $backupId
        ]);

        // Crear tabla de backup si no existe
        DB::statement("
            CREATE TABLE IF NOT EXISTS {$this->backupTable} (
                backup_id VARCHAR(100),
                document_id INT,
                original_health_fields LONGTEXT,
                backup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_backup_id (backup_id),
                INDEX idx_document_id (document_id)
            ) ENGINE=InnoDB
        ");

        // Crear backup de documentos con health_fields corruptos
        $backedUpCount = DB::statement("
            INSERT INTO {$this->backupTable} (backup_id, document_id, original_health_fields)
            SELECT 
                '{$backupId}' as backup_id,
                id as document_id,
                health_fields as original_health_fields
            FROM documents 
            WHERE health_fields IS NOT NULL 
                AND JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields')
        ");

        $backupCount = DB::scalar("
            SELECT COUNT(*) FROM {$this->backupTable} WHERE backup_id = '{$backupId}'
        ");

        Log::info('✅ ENTERPRISE BACKUP: Backup created successfully', [
            'backup_id' => $backupId,
            'documents_backed_up' => $backupCount
        ]);

        return $backupId;
    }

    /**
     * ENTERPRISE: Corrige un documento individual con validación completa
     */
    public function fixSingleDocument(int $documentId): array
    {
        Log::info('🔧 ENTERPRISE FIX: Starting single document correction', [
            'document_id' => $documentId
        ]);

        try {
            // Obtener documento actual
            $document = DB::selectOne("
                SELECT id, health_fields, LENGTH(health_fields) as original_size
                FROM documents 
                WHERE id = ? AND health_fields IS NOT NULL
            ", [$documentId]);

            if (!$document) {
                throw new Exception("Document {$documentId} not found or has no health_fields");
            }

            $originalHealthFields = json_decode($document->health_fields, true);
            
            if (!$originalHealthFields) {
                throw new Exception("Invalid JSON in health_fields for document {$documentId}");
            }

            // Verificar si necesita corrección
            if (!isset($originalHealthFields['health_fields'])) {
                Log::info('ℹ️ ENTERPRISE FIX: Document already clean', ['document_id' => $documentId]);
                return [
                    'status' => 'already_clean',
                    'document_id' => $documentId,
                    'message' => 'Document does not need correction'
                ];
            }

            // Aplicar limpieza usando el servicio existente
            $cleanedHealthFields = $this->healthFieldsCleaner->cleanAndConsolidateHealthFields($originalHealthFields);

            // Validar estructura limpia
            $this->validateCleanedStructure($cleanedHealthFields);

            // Actualizar documento
            $newSize = strlen(json_encode($cleanedHealthFields));
            
            DB::update("
                UPDATE documents 
                SET health_fields = ? 
                WHERE id = ?
            ", [json_encode($cleanedHealthFields), $documentId]);

            $result = [
                'status' => 'success',
                'document_id' => $documentId,
                'original_size' => $document->original_size,
                'new_size' => $newSize,
                'size_reduction' => $document->original_size - $newSize,
                'size_reduction_percentage' => round((($document->original_size - $newSize) / $document->original_size) * 100, 1)
            ];

            Log::info('✅ ENTERPRISE FIX: Document corrected successfully', $result);
            return $result;

        } catch (Exception $e) {
            Log::error('❌ ENTERPRISE FIX: Document correction failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            
            return [
                'status' => 'error',
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ENTERPRISE: Corrección masiva con batch processing y progress tracking
     */
    public function fixAllCorruptedDocuments(int $batchSize = 10): array
    {
        Log::info('🚀 ENTERPRISE BATCH FIX: Starting mass correction of corrupted documents', [
            'batch_size' => $batchSize
        ]);

        // Crear backup automático
        $backupId = $this->createBackup();

        // Obtener documentos corruptos
        $corruptedDocuments = DB::select("
            SELECT id
            FROM documents 
            WHERE health_fields IS NOT NULL 
                AND JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields')
            ORDER BY id ASC
        ");

        $totalDocuments = count($corruptedDocuments);
        $results = [
            'backup_id' => $backupId,
            'total_documents' => $totalDocuments,
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'already_clean' => 0,
            'total_size_reduction' => 0,
            'details' => [],
            'errors_details' => []
        ];

        if ($totalDocuments === 0) {
            Log::info('ℹ️ ENTERPRISE BATCH FIX: No corrupted documents found');
            return $results;
        }

        // Procesar en batches
        $batches = array_chunk($corruptedDocuments, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            Log::info("🔄 ENTERPRISE BATCH FIX: Processing batch " . ($batchIndex + 1) . "/" . count($batches));
            
            foreach ($batch as $document) {
                $fixResult = $this->fixSingleDocument($document->id);
                
                $results['processed']++;
                
                switch ($fixResult['status']) {
                    case 'success':
                        $results['success']++;
                        $results['total_size_reduction'] += $fixResult['size_reduction'];
                        break;
                    case 'already_clean':
                        $results['already_clean']++;
                        break;
                    case 'error':
                        $results['errors']++;
                        $results['errors_details'][] = $fixResult;
                        break;
                }
                
                $results['details'][] = $fixResult;
            }
        }

        Log::info('✅ ENTERPRISE BATCH FIX: Mass correction completed', [
            'total_processed' => $results['processed'],
            'success_count' => $results['success'],
            'error_count' => $results['errors'],
            'total_size_reduction_bytes' => $results['total_size_reduction']
        ]);

        return $results;
    }

    /**
     * ENTERPRISE: Rollback utilizando backup
     */
    public function rollbackUsingBackup(string $backupId): array
    {
        Log::info('🔄 ENTERPRISE ROLLBACK: Starting rollback operation', [
            'backup_id' => $backupId
        ]);

        try {
            // Verificar que el backup existe
            $backupCount = DB::scalar("
                SELECT COUNT(*) FROM {$this->backupTable} WHERE backup_id = ?
            ", [$backupId]);

            if ($backupCount === 0) {
                throw new Exception("Backup {$backupId} not found");
            }

            // Restaurar documentos desde backup
            $restored = DB::update("
                UPDATE documents d
                INNER JOIN {$this->backupTable} b ON d.id = b.document_id
                SET d.health_fields = b.original_health_fields
                WHERE b.backup_id = ?
            ", [$backupId]);

            Log::info('✅ ENTERPRISE ROLLBACK: Rollback completed successfully', [
                'backup_id' => $backupId,
                'documents_restored' => $restored
            ]);

            return [
                'status' => 'success',
                'backup_id' => $backupId,
                'documents_restored' => $restored
            ];

        } catch (Exception $e) {
            Log::error('❌ ENTERPRISE ROLLBACK: Rollback failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'backup_id' => $backupId,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida que la estructura limpia sea correcta
     */
    private function validateCleanedStructure(array $cleanedData): void
    {
        // No debe haber health_fields anidados
        if (isset($cleanedData['health_fields'])) {
            throw new Exception('Cleaned structure still contains nested health_fields');
        }

        // Debe tener health_type_operation_id
        if (!isset($cleanedData['health_type_operation_id'])) {
            throw new Exception('Cleaned structure missing health_type_operation_id');
        }

        // Debe tener users_info válido
        if (!isset($cleanedData['users_info']) || !is_array($cleanedData['users_info'])) {
            throw new Exception('Cleaned structure missing or invalid users_info');
        }

        // Validar fechas
        if (!isset($cleanedData['invoice_period_start_date']) || !isset($cleanedData['invoice_period_end_date'])) {
            throw new Exception('Cleaned structure missing invoice period dates');
        }
    }
}