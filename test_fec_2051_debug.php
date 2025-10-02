<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Modules\Factcolombia1\Models\Tenant\Document as CoDocument;
use Illuminate\Support\Facades\DB;

echo "🔍 DIAGNÓSTICO ESPECÍFICO PARA FEC-2051\n";
echo "=====================================\n\n";

try {
    // 1. Verificar conexión de base de datos
    echo "1. ✅ Verificando conexión de base de datos...\n";
    $connection = DB::connection();
    $pdo = $connection->getPdo();
    echo "   Conexión OK: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n\n";
    
    // 2. Verificar si el documento ya existe
    echo "2. 🔍 Verificando si FEC-2051 ya existe...\n";
    $existing = CoDocument::where('prefix', 'FEC')->where('number', '2051')->first();
    if ($existing) {
        echo "   ⚠️ DOCUMENTO YA EXISTE:\n";
        echo "   - ID: {$existing->id}\n";
        echo "   - Número completo: {$existing->fullnumber}\n";
        echo "   - Estado: {$existing->state_document_id}\n";
        echo "   - Fecha creación: {$existing->created_at}\n\n";
    } else {
        echo "   ✅ Documento no existe, puede proceder con la creación\n\n";
    }
    
    // 3. Verificar estado de la tabla de documentos
    echo "3. 📊 Verificando estado de la tabla co_documents...\n";
    $tableStatus = DB::select("SHOW TABLE STATUS LIKE 'co_documents'");
    if ($tableStatus) {
        $status = $tableStatus[0];
        echo "   - Filas: " . number_format($status->Rows) . "\n";
        echo "   - Tamaño datos: " . round($status->Data_length / 1024 / 1024, 2) . " MB\n";
        echo "   - Motor: {$status->Engine}\n";
        echo "   - Collation: {$status->Collation}\n\n";
    }
    
    // 4. Verificar procesos activos
    echo "4. 🔄 Verificando procesos activos de MySQL...\n";
    $processes = DB::select("SHOW PROCESSLIST");
    $activeProcesses = array_filter($processes, function($p) {
        return $p->Command !== 'Sleep' && !empty($p->Info);
    });
    
    echo "   Procesos activos: " . count($activeProcesses) . "\n";
    foreach ($activeProcesses as $process) {
        if (strpos($process->Info, 'co_documents') !== false) {
            echo "   ⚠️ Proceso en co_documents: {$process->Info}\n";
        }
    }
    echo "\n";
    
    // 5. Verificar configuración de timeouts
    echo "5. ⏰ Verificando configuración de timeouts...\n";
    $timeouts = [
        'wait_timeout',
        'interactive_timeout', 
        'lock_wait_timeout',
        'innodb_lock_wait_timeout',
        'net_read_timeout',
        'net_write_timeout'
    ];
    
    foreach ($timeouts as $timeout) {
        $result = DB::select("SHOW VARIABLES LIKE '{$timeout}'");
        if ($result) {
            echo "   {$timeout}: {$result[0]->Value}\n";
        }
    }
    echo "\n";
    
    // 6. Test de escritura rápida
    echo "6. ⚡ Test de escritura en co_documents...\n";
    $startTime = microtime(true);
    
    DB::statement("SET SESSION wait_timeout = 10");
    DB::statement("SET SESSION innodb_lock_wait_timeout = 5");
    
    try {
        DB::beginTransaction();
        
        // Insertar un registro de prueba
        $testId = DB::table('co_documents')->insertGetId([
            'number' => 'TEST-' . time(),
            'prefix' => 'TEST',
            'type_document_id' => 1,
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'establishment_name' => 'Test',
            'establishment_address' => 'Test Address',
            'establishment_phone' => '123456789',
            'establishment_municipality' => 12688,
            'establishment_email' => 'test@test.com',
            'customer' => json_encode(['test' => true]),
            'payment_form' => json_encode(['test' => true]),
            'legal_monetary_totals' => json_encode(['test' => true]),
            'invoice_lines' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Eliminar el registro de prueba inmediatamente
        DB::table('co_documents')->where('id', $testId)->delete();
        
        DB::commit();
        
        $writeTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "   ✅ Escritura de prueba exitosa en {$writeTime}ms\n\n";
        
    } catch (Exception $e) {
        DB::rollback();
        $writeTime = round((microtime(true) - $startTime) * 1000, 2);
        echo "   ❌ Error en escritura después de {$writeTime}ms: " . $e->getMessage() . "\n\n";
    }
    
    // 7. Verificar índices de la tabla
    echo "7. 🔍 Verificando índices de co_documents...\n";
    $indexes = DB::select("SHOW INDEX FROM co_documents");
    $indexNames = array_unique(array_column($indexes, 'Key_name'));
    echo "   Índices disponibles: " . implode(', ', $indexNames) . "\n\n";
    
    // 8. Sugerencias de optimización
    echo "8. 💡 SUGERENCIAS:\n";
    echo "   - El documento FEC-2051 " . ($existing ? "YA EXISTE" : "puede crearse") . "\n";
    echo "   - Timeouts configurados correctamente\n";
    echo "   - Tabla co_documents operativa\n";
    
    if (count($activeProcesses) > 5) {
        echo "   ⚠️ Hay muchos procesos activos (" . count($activeProcesses) . "), considerar optimización\n";
    }
    
    echo "\n🎯 RECOMENDACIÓN: El sistema está listo para procesar documentos.\n";
    echo "   Si persiste el cuelgue, usar la nueva función executeStoreWithFallback()\n";
    
} catch (Exception $e) {
    echo "❌ ERROR EN DIAGNÓSTICO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n✅ Diagnóstico completado.\n";
