<?php

// Script para probar directamente desde el contenedor de la aplicación
require_once '/var/www/html/vendor/autoload.php';

// Simular una petición HTTP básica
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/co-documents/health/find-user-by-document?documento=1018478818&tipo_documento=CC';
$_SERVER['HTTP_HOST'] = 'ddb.imperiumfevsrips.net';
$_SERVER['HTTPS'] = 'on';

// Parámetros de prueba
$_GET['documento'] = '1018478818';
$_GET['tipo_documento'] = 'CC';

echo "=== PRUEBA DIRECTA DEL CONTROLADOR ===\n";
echo "Documento: 1018478818\n";
echo "Tipo: CC\n";
echo "---\n";

try {
    // Inicializar Laravel
    $app = require_once '/var/www/html/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Configurar entorno de tenancy para ddb
    $hostname = \Hyn\Tenancy\Models\Hostname::where('fqdn', 'ddb.imperiumfevsrips.net')->first();
    if ($hostname) {
        app('Hyn\Tenancy\Environment')->hostname($hostname);
        echo "✓ Hostname configurado: " . $hostname->fqdn . "\n";
        echo "✓ Website ID: " . $hostname->website_id . "\n";
    } else {
        echo "✗ Hostname no encontrado\n";
        // Listar hostnames disponibles
        $hostnames = \Hyn\Tenancy\Models\Hostname::all();
        echo "Hostnames disponibles:\n";
        foreach ($hostnames as $h) {
            echo "  - " . $h->fqdn . " (ID: " . $h->id . ")\n";
        }
        exit;
    }
    
    echo "✓ Laravel y Tenancy inicializados\n";
    
    // Crear instancia del controlador
    $controller = new \Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController();
    
    // Crear request mock
    $request = new \Illuminate\Http\Request();
    $request->merge(['documento' => '1018478818', 'tipo_documento' => 'CC']);
    
    echo "✓ Request creado\n";
    echo "Ejecutando método...\n";
    
    // Llamar al método
    $response = $controller->find_health_user_by_document($request);
    
    echo "✓ Método ejecutado\n";
    echo "Respuesta:\n";
    echo $response->getContent();
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
