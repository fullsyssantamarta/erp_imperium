<?php

// Script de prueba simple
$urls = [
    'https://tenancy-ddb.imperiumfevsrips.net/co-documents',
    'https://tenancy-ddb.imperiumfevsrips.net/co-documents/health/test-connection'
];

foreach ($urls as $url) {
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

    if ($response !== false) {
        if (strlen($response) > 200) {
            echo "Response (primeros 200 chars): " . substr($response, 0, 200) . "...\n";
        } else {
            echo "Response: $response\n";
        }
    } else {
        echo "Error: No se pudo obtener respuesta\n";
        if (isset($http_response_header)) {
            echo "Headers: \n";
            print_r($http_response_header);
        }
    }
    echo "---\n";
}
