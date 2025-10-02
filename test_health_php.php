<?php

// Script para simular creación de factura de salud desde PHP
// Se ejecuta dentro del contexto de Laravel

require_once '/var/www/html/bootstrap/app.php';

use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Illuminate\Http\Request;

echo "=== SIMULACIÓN INTERNA DE FACTURA DE SALUD ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Configurar tenant
putenv('TENANT_DATABASE=tenancy_ddb');

try {
    echo "1. Inicializando aplicación Laravel...\n";
    $app = require_once '/var/www/html/bootstrap/app.php';
    
    echo "2. Creando request simulado...\n";
    $request = Request::create('/co-documents', 'POST', [
        'type_document_id' => '1',
        'customer' => [
            'identification_number' => '1018478818',
            'dv' => '5', 
            'name' => 'PINEDA MARTIN AURA MILENA',
            'phone' => '7432597',
            'address' => 'TV 93 #51-98',
            'email' => 'facturaelectronica@ddb.com.co',
            'type_document_identification_id' => '6',
            'type_organization_id' => '1',
            'municipality_id' => '779',
            'type_regime_id' => '2'
        ],
        'health_fields' => [
            'medical_information' => 'VENLAFAXINA 75mg (WYETH) CAP (30)',
            'diagnosis_code' => 'Z000',
            'procedure_code' => '227311-3',
            'procedure_value' => '947.00'
        ],
        'items' => [
            [
                'item' => [
                    'id' => '1',
                    'description' => 'VENLAFAXINA 75mg (WYETH) CAP (30)',
                    'unit_price' => 947.00,
                    'item_type_id' => '1'
                ],
                'quantity' => 1
            ]
        ]
    ]);
    
    echo "3. Intentando procesar factura...\n";
    $controller = new DocumentController();
    $response = $controller->store($request);
    
    echo "4. Respuesta obtenida:\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: \n" . $e->getTraceAsString() . "\n";
}

echo "\nSimulación completada.\n";
?>