<?php

// Script simple para probar el HealthFieldsCleanerService
// Usar el autoloader de composer directamente

chdir('/var/www/html');
require_once '/var/www/html/vendor/autoload.php';

// Cargar Laravel
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRUEBA HEALTHFIELDS CLEANER SERVICE ===\n";

try {
    // Crear instancia del servicio
    $cleaner = new \App\Services\HealthFieldsCleanerService();
    echo "✅ Servicio HealthFieldsCleanerService creado\n";

    // Datos problemáticos simulando el caso real
    $datosProblematicos = [
        "health_type_operation_id" => 8, // Valor incorrecto
        "users_info" => [
            [
                "identification_number" => "39520490",
                "first_name" => "MARIA", 
                "surname" => "GIL"
            ]
        ],
        "health_fields" => [
            "invoice_period_start_date" => "2025-09-01",
            "invoice_period_end_date" => "2025-09-14",
            "health_type_operation_id" => 1, // Valor correcto (conflicto)
            "users_info" => [
                [
                    "identification_number" => "39520490",
                    "first_name" => "MARIA",
                    "surname" => "GIL"
                ]
            ]
        ]
    ];

    echo "🔄 Procesando datos con duplicaciones...\n";
    
    $resultado = $cleaner->cleanAndConsolidateHealthFields($datosProblematicos);
    
    echo "✅ Limpieza completada\n";
    echo "📊 Resultado:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n";
    
    // Validaciones
    $success = true;
    if ($resultado['health_type_operation_id'] !== 1) {
        echo "❌ health_type_operation_id incorrecto: " . $resultado['health_type_operation_id'] . "\n";
        $success = false;
    }
    
    if (empty($resultado['users_info'])) {
        echo "❌ users_info está vacío\n";
        $success = false;
    }
    
    if ($success) {
        echo "🎉 ¡PRUEBA EXITOSA! El servicio funciona correctamente\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN PRUEBA ===\n";