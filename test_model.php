<?php

// Script de prueba para verificar el estado de la aplicación
echo "=== PRUEBA DE CONEXIÓN Y MODELO ===\n";

// Verificar que Laravel se puede inicializar
try {
    require_once '/var/www/html/vendor/autoload.php';
    $app = require_once '/var/www/html/bootstrap/app.php';
    echo "✓ Laravel inicializado correctamente\n";
    
    // Verificar conexión de base de datos
    try {
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        echo "✓ Kernel inicializado\n";
        
        // Verificar si el modelo existe
        if (class_exists('App\Tenant\TenancyHealthUser')) {
            echo "✓ Modelo TenancyHealthUser existe\n";
            
            // Intentar usar el modelo
            try {
                $count = App\Tenant\TenancyHealthUser::count();
                echo "✓ Conexión DB OK - Total registros: $count\n";
            } catch (Exception $e) {
                echo "✗ Error al consultar modelo: " . $e->getMessage() . "\n";
                echo "  - Archivo: " . $e->getFile() . "\n";
                echo "  - Línea: " . $e->getLine() . "\n";
            }
        } else {
            echo "✗ Modelo TenancyHealthUser NO existe\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error al inicializar kernel: " . $e->getMessage() . "\n";
        echo "  - Archivo: " . $e->getFile() . "\n";
        echo "  - Línea: " . $e->getLine() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error al inicializar Laravel: " . $e->getMessage() . "\n";
    echo "  - Archivo: " . $e->getFile() . "\n";
    echo "  - Línea: " . $e->getLine() . "\n";
}
