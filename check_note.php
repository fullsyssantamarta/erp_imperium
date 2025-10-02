<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant\Document;

// Buscar la nota de crédito NC-9000012
$doc = Document::where('number', 9000012)->where('type_document_id', 4)->first();

if($doc) {
    echo "Documento encontrado: " . $doc->prefix . $doc->number . PHP_EOL;
    echo "Health fields: " . json_encode($doc->health_fields) . PHP_EOL;
    echo "XML file: " . $doc->xml . PHP_EOL;
    
    // Buscar el archivo XML
    if($doc->xml) {
        $xmlPath = storage_path("app/public/" . $doc->establishment->user->identification_number . "/" . $doc->xml);
        if(file_exists($xmlPath)) {
            echo "XML encontrado en: " . $xmlPath . PHP_EOL;
        } else {
            echo "XML no encontrado en ruta esperada: " . $xmlPath . PHP_EOL;
        }
    }
} else {
    echo "Documento no encontrado" . PHP_EOL;
}
