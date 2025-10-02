<?php

/**
 * Script Laravel para probar HealthFieldsCleanerService en contenedor Docker
 * Ejecutar: docker exec fmp_app php /var/www/html/test_health_cleaner_laravel.php
 */

require_once '/var/www/html/bootstrap/app.php';

$app = app();

echo "=== PRUEBA HEALTHFIELDS CLEANER SERVICE EN LARAVEL ===\n\n";

try {
    // Obtener el servicio
    $healthCleaner = app(\App\Services\HealthFieldsCleanerService::class);
    echo "✅ Servicio HealthFieldsCleanerService cargado exitosamente\n\n";

    // Datos problemáticos con duplicaciones (como los reales que encontramos)
    $datosProblematicos = [
        "invoice_period_start_date" => "2025-09-01",
        "invoice_period_end_date" => "2025-09-14",
        "health_type_operation_id" => 8, // ❌ VALOR INCORRECTO 
        "users_info" => [
            [
                "provider_code" => "1100187015",
                "health_type_document_identification_id" => 1,
                "identification_number" => "39520490",
                "surname" => "GIL",
                "second_surname" => "AVELLA",
                "first_name" => "MARIA", 
                "middle_name" => "MERCEDES",
                "health_type_user_id" => 4,
                "health_contracting_payment_method_id" => 4,
                "health_coverage_id" => 1,
                "autorization_numbers" => "90925167",
                "contract_number" => "S-13-1-08-1-04619",
                "co_payment" => "0"
            ]
        ],
        // ❌ DUPLICACIÓN: health_fields anidado
        "health_fields" => [
            "invoice_period_start_date" => "2025-09-01", 
            "invoice_period_end_date" => "2025-09-14",
            "health_type_operation_id" => 1, // ❌ CONFLICTO: 8 vs 1
            "users_info" => [
                [
                    "provider_code" => "1100187015",
                    "health_type_document_identification_id" => 1,
                    "identification_number" => "39520490",
                    "surname" => "GIL", 
                    "second_surname" => "AVELLA",
                    "first_name" => "MARIA",
                    "middle_name" => "MERCEDES",
                    "health_type_user_id" => 4,
                    "health_contracting_payment_method_id" => 4,
                    "health_coverage_id" => 1,
                    "autorization_numbers" => "90925167",
                    "contract_number" => "S-13-1-08-1-04619",
                    "co_payment" => "0"
                ]
            ]
        ]
    ];

    echo "🔄 PROCESANDO DATOS CON DUPLICACIONES...\n";
    
    // Ejecutar limpieza
    $resultadoLimpio = $healthCleaner->cleanAndConsolidateHealthFields($datosProblematicos);
    
    echo "✅ LIMPIEZA COMPLETADA!\n\n";
    
    echo "📊 RESULTADO FINAL:\n";
    echo json_encode($resultadoLimpio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    echo "\n\n🔍 VALIDACIONES:\n";
    echo "- health_type_operation_id: " . $resultadoLimpio['health_type_operation_id'] . " (debe ser 1)\n";
    echo "- users_info count: " . count($resultadoLimpio['users_info']) . " (debe ser 1)\n";
    echo "- Fechas período: " . $resultadoLimpio['invoice_period_start_date'] . " a " . $resultadoLimpio['invoice_period_end_date'] . "\n";
    
    // Validar estructura
    $camposRequeridos = ['invoice_period_start_date', 'invoice_period_end_date', 'health_type_operation_id', 'users_info'];
    $todosPresentes = true;
    
    foreach ($camposRequeridos as $campo) {
        if (!isset($resultadoLimpio[$campo])) {
            echo "❌ FALTA CAMPO: $campo\n";
            $todosPresentes = false;
        } else {
            echo "✅ CAMPO OK: $campo\n";
        }
    }
    
    if ($todosPresentes && $resultadoLimpio['health_type_operation_id'] === 1) {
        echo "\n🎉 ¡PRUEBA EXITOSA! El servicio funciona correctamente.\n";
    } else {
        echo "\n❌ PRUEBA FALLIDA. Revisar implementación.\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBA ===\n";