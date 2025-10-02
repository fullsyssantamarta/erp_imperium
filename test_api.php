<?php

// Script de prueba simple
$url = 'https://tenancy-ddb.imperiumfevsrips.net/co-documents/health/find-user-by-document?documento=1018478818&tipo_documento=CC';

echo "Probando URL: $url\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = file_get_contents($url, false, $context);

echo "Response: " . $response . "\n";

if ($response === false) {
    echo "Error: No se pudo obtener respuesta\n";
    echo "Headers: \n";
    print_r($http_response_header);
}
