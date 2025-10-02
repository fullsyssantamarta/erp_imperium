<?php

/**
 * 🏗️ ENTERPRISE MASS CORRECTION: Health Fields Data Optimizer
 * Script para corrección masiva de health_fields corruptos
 */

echo "🏗️ ENTERPRISE MASS CORRECTION: Health Fields Data Optimizer\n";
echo "==========================================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "User: " . get_current_user() . "\n\n";

if ($argc < 2) {
    echo "Usage: php enterprise_health_mass_fix.php [analyze|fix-all|rollback]\n";
    echo "\nActions:\n";
    echo "  analyze   - Analyze corruption scope\n";
    echo "  fix-all   - Fix all corrupted documents (creates backup)\n";
    echo "  rollback  - Restore from backup table\n";
    exit(1);
}

$action = $argv[1];

try {
    $pdo = new PDO('mysql:host=mariadb;dbname=tenancy_thelabs;charset=utf8mb4', 'root', 'p3C7D3ZkwDC5BosXILqp');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection established\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

switch ($action) {
    case 'analyze':
        performDetailedAnalysis($pdo);
        break;
    case 'fix-all':
        performMassCorrection($pdo);
        break;
    case 'rollback':
        performRollback($pdo);
        break;
    default:
        echo "❌ Invalid action. Use: analyze, fix-all, or rollback\n";
        exit(1);
}

/**
 * Análisis detallado del problema
 */
function performDetailedAnalysis(PDO $pdo): void
{
    echo "🔍 DETAILED CORRUPTION ANALYSIS\n";
    echo "===============================\n";
    
    // Estadísticas generales
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_with_health_fields,
            SUM(CASE WHEN JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields') THEN 1 ELSE 0 END) as corrupted_count,
            AVG(LENGTH(health_fields)) as avg_size_bytes,
            MIN(LENGTH(health_fields)) as min_size_bytes,
            MAX(LENGTH(health_fields)) as max_size_bytes
        FROM documents 
        WHERE health_fields IS NOT NULL AND health_fields != '' AND health_fields != '{}'
    ");
    
    $analysis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 SUMMARY:\n";
    echo "   Total documents with health_fields: " . $analysis['total_with_health_fields'] . "\n";
    echo "   Corrupted documents: " . $analysis['corrupted_count'] . "\n";
    echo "   Clean documents: " . ($analysis['total_with_health_fields'] - $analysis['corrupted_count']) . "\n";
    echo "   Corruption percentage: " . round(($analysis['corrupted_count'] / $analysis['total_with_health_fields']) * 100, 1) . "%\n";
    echo "   Average size: " . round($analysis['avg_size_bytes']) . " bytes\n";
    echo "   Size range: " . $analysis['min_size_bytes'] . " - " . $analysis['max_size_bytes'] . " bytes\n\n";
    
    if ($analysis['corrupted_count'] > 0) {
        echo "📋 CORRUPTED DOCUMENTS DETAILS:\n";
        $stmt = $pdo->query("
            SELECT id, number, type_document_id, date_of_issue, LENGTH(health_fields) as size_bytes,
                   JSON_EXTRACT(health_fields, '$.health_type_operation_id') as root_operation_id,
                   JSON_EXTRACT(health_fields, '$.health_fields.health_type_operation_id') as nested_operation_id
            FROM documents 
            WHERE health_fields IS NOT NULL 
                AND JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields')
            ORDER BY size_bytes DESC, date_of_issue DESC
        ");
        
        $totalSizeBefore = 0;
        $estimatedSizeAfter = 0;
        $count = 0;
        
        while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count++;
            $totalSizeBefore += $doc['size_bytes'];
            $estimatedSizeAfter += round($doc['size_bytes'] * 0.32); // Estimación basada en 68% reducción
            
            echo "   {$count}. ID: {$doc['id']}, Number: {$doc['number']}, Date: {$doc['date_of_issue']}, Size: {$doc['size_bytes']} bytes\n";
            echo "      Root operation_id: {$doc['root_operation_id']}, Nested: {$doc['nested_operation_id']}\n";
            
            if ($count >= 15) {
                $remaining = $analysis['corrupted_count'] - $count;
                if ($remaining > 0) {
                    echo "   ... and {$remaining} more corrupted documents\n";
                }
                break;
            }
        }
        
        $totalReduction = $totalSizeBefore - $estimatedSizeAfter;
        $reductionPercentage = round(($totalReduction / $totalSizeBefore) * 100, 1);
        
        echo "\n📈 ESTIMATED OPTIMIZATION RESULTS:\n";
        echo "   Current total size: " . number_format($totalSizeBefore) . " bytes\n";
        echo "   Estimated size after fix: " . number_format($estimatedSizeAfter) . " bytes\n";
        echo "   Estimated total reduction: " . number_format($totalReduction) . " bytes ({$reductionPercentage}%)\n";
        
        echo "\n🚀 NEXT STEPS:\n";
        echo "   To fix all corrupted documents: php enterprise_health_mass_fix.php fix-all\n";
        echo "   ⚠️  This will create a backup table before making changes\n";
    } else {
        echo "\n✅ NO CORRUPTION DETECTED!\n";
    }
}

/**
 * Corrección masiva de todos los documentos corruptos
 */
function performMassCorrection(PDO $pdo): void
{
    echo "🔧 ENTERPRISE MASS CORRECTION\n";
    echo "============================\n";
    
    // Verificar si ya existe backup
    $backupExists = false;
    try {
        $pdo->query("SELECT 1 FROM documents_health_fields_backup LIMIT 1");
        $backupExists = true;
        echo "ℹ️  Backup table already exists: documents_health_fields_backup\n";
    } catch (Exception $e) {
        echo "📦 Creating backup table...\n";
    }
    
    // Obtener documentos corruptos
    $stmt = $pdo->query("
        SELECT id, health_fields, LENGTH(health_fields) as original_size
        FROM documents 
        WHERE health_fields IS NOT NULL 
            AND JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields')
        ORDER BY id
    ");
    
    $corruptedDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalDocs = count($corruptedDocs);
    
    if ($totalDocs === 0) {
        echo "✅ No corrupted documents found. Nothing to fix.\n";
        return;
    }
    
    echo "📊 Found {$totalDocs} corrupted documents to fix\n";
    echo "⚠️  This operation will modify the database. Continue? (yes/no): ";
    
    $confirmation = trim(fgets(STDIN));
    if (strtolower($confirmation) !== 'yes') {
        echo "❌ Operation cancelled by user\n";
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Crear tabla de backup si no existe
        if (!$backupExists) {
            $pdo->exec("
                CREATE TABLE documents_health_fields_backup AS 
                SELECT id, health_fields, NOW() as backup_date
                FROM documents 
                WHERE health_fields IS NOT NULL 
                    AND JSON_CONTAINS_PATH(health_fields, 'one', '$.health_fields')
            ");
            echo "✅ Backup table created with {$totalDocs} records\n";
        }
        
        // Procesar documentos en lotes
        $batchSize = 10;
        $processed = 0;
        $totalSizeBefore = 0;
        $totalSizeAfter = 0;
        
        echo "\n🔄 Processing documents in batches of {$batchSize}...\n";
        
        $updateStmt = $pdo->prepare("UPDATE documents SET health_fields = ? WHERE id = ?");
        
        foreach (array_chunk($corruptedDocs, $batchSize) as $batch) {
            foreach ($batch as $doc) {
                $originalData = json_decode($doc['health_fields'], true);
                $cleanData = cleanHealthFields($originalData);
                $cleanedJson = json_encode($cleanData);
                
                $updateStmt->execute([$cleanedJson, $doc['id']]);
                
                $processed++;
                $totalSizeBefore += $doc['original_size'];
                $totalSizeAfter += strlen($cleanedJson);
                
                $progress = round(($processed / $totalDocs) * 100, 1);
                echo "   ✓ Document ID {$doc['id']} fixed ({$progress}% complete)\n";
            }
            
            // Pausa pequeña entre lotes para no sobrecargar
            usleep(100000); // 0.1 segundos
        }
        
        $pdo->commit();
        
        $totalReduction = $totalSizeBefore - $totalSizeAfter;
        $reductionPercentage = round(($totalReduction / $totalSizeBefore) * 100, 1);
        
        echo "\n🎉 MASS CORRECTION COMPLETED SUCCESSFULLY!\n";
        echo "========================================\n";
        echo "   Documents processed: {$processed}\n";
        echo "   Original total size: " . number_format($totalSizeBefore) . " bytes\n";
        echo "   New total size: " . number_format($totalSizeAfter) . " bytes\n";
        echo "   Total size reduction: " . number_format($totalReduction) . " bytes ({$reductionPercentage}%)\n";
        echo "   Backup table: documents_health_fields_backup\n";
        
        echo "\n📋 ROLLBACK INSTRUCTIONS (if needed):\n";
        echo "   php enterprise_health_mass_fix.php rollback\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ ERROR during mass correction: " . $e->getMessage() . "\n";
        echo "🔄 All changes have been rolled back\n";
    }
}

/**
 * Rollback usando la tabla de backup
 */
function performRollback(PDO $pdo): void
{
    echo "🔄 ENTERPRISE ROLLBACK OPERATION\n";
    echo "===============================\n";
    
    // Verificar si existe tabla de backup
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as backup_count FROM documents_health_fields_backup");
        $backupInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "📦 Found backup table with {$backupInfo['backup_count']} records\n";
    } catch (Exception $e) {
        echo "❌ Backup table not found. Cannot perform rollback.\n";
        return;
    }
    
    echo "⚠️  This will restore all health_fields from backup. Continue? (yes/no): ";
    $confirmation = trim(fgets(STDIN));
    
    if (strtolower($confirmation) !== 'yes') {
        echo "❌ Rollback cancelled by user\n";
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->exec("
            UPDATE documents d 
            JOIN documents_health_fields_backup b ON d.id = b.id 
            SET d.health_fields = b.health_fields
        ");
        
        $pdo->commit();
        
        echo "✅ ROLLBACK COMPLETED SUCCESSFULLY!\n";
        echo "   Restored {$stmt} documents from backup\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ ERROR during rollback: " . $e->getMessage() . "\n";
    }
}

/**
 * Limpiar health_fields (lógica del HealthFieldsCleanerService)
 */
function cleanHealthFields(array $rawData): array
{
    $cleanData = [];
    
    // Extraer fechas de período (priorizar health_fields anidados)
    if (isset($rawData['health_fields']['invoice_period_start_date'])) {
        $cleanData['invoice_period_start_date'] = $rawData['health_fields']['invoice_period_start_date'];
    } elseif (isset($rawData['invoice_period_start_date'])) {
        $cleanData['invoice_period_start_date'] = $rawData['invoice_period_start_date'];
    }
    
    if (isset($rawData['health_fields']['invoice_period_end_date'])) {
        $cleanData['invoice_period_end_date'] = $rawData['health_fields']['invoice_period_end_date'];
    } elseif (isset($rawData['invoice_period_end_date'])) {
        $cleanData['invoice_period_end_date'] = $rawData['invoice_period_end_date'];
    }
    
    // Consolidar health_type_operation_id (priorizar health_fields anidados)
    if (isset($rawData['health_fields']['health_type_operation_id'])) {
        $cleanData['health_type_operation_id'] = $rawData['health_fields']['health_type_operation_id'];
    } elseif (isset($rawData['health_type_operation_id'])) {
        $cleanData['health_type_operation_id'] = $rawData['health_type_operation_id'];
    } else {
        $cleanData['health_type_operation_id'] = 1; // Por defecto
    }
    
    // Consolidar users_info (priorizar health_fields anidados)
    if (isset($rawData['health_fields']['users_info']) && is_array($rawData['health_fields']['users_info'])) {
        $cleanData['users_info'] = $rawData['health_fields']['users_info'];
    } elseif (isset($rawData['users_info']) && is_array($rawData['users_info'])) {
        $cleanData['users_info'] = $rawData['users_info'];
    } else {
        $cleanData['users_info'] = [];
    }
    
    // Agregar print_users_info_to_pdf
    $cleanData['print_users_info_to_pdf'] = true;
    
    return $cleanData;
}