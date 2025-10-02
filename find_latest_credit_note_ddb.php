<?php
// Helper script: Find latest credit note in DDB tenant

require 'vendor/autoload.php';

// Force tenant DB for DDB
putenv('TENANT_DATABASE=tenancy_ddb');

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant\Document;

echo "=== Buscar nota crédito más reciente (tenant: DDB) ===\n";

try {
    // Prefer standard credit notes (4). Include POS credit notes (26) just in case.
    $doc = Document::whereIn('type_document_id', [4, 26])
        ->orderByDesc('date_of_issue')
        ->orderByDesc('number')
        ->first();

    if (!$doc) {
        echo "No se encontraron notas de crédito en tenancy_ddb.\n";
        exit(0);
    }

    $fullNumber = trim(($doc->prefix ?? '') . $doc->number);
    echo sprintf(
        "ID: %s | Número: %s | Tipo: %s | Fecha: %s | Creado: %s\n",
        $doc->id,
        $fullNumber,
        $doc->type_document_id,
        (string) $doc->date_of_issue,
        (string) $doc->created_at
    );

    // Basic health fields summary
    $hf = $doc->health_fields ? (is_string($doc->health_fields) ? json_decode($doc->health_fields, true) : $doc->health_fields) : null;
    if ($hf) {
        $users = $hf['users_info'] ?? [];
        $userCount = is_array($users) ? count($users) : 0;
        echo "health_fields: presente | users_info: {$userCount}\n";
    } else {
        echo "health_fields: no presente\n";
    }

    // XML info
    if (!empty($doc->xml)) {
        echo "XML file: {$doc->xml}\n";
        // Try to resolve absolute path if establishment->user available
        try {
            $establishment = $doc->establishment ?? null; // may be casted as object in data_json too
            $ident = null;
            if ($establishment && isset($establishment->user) && isset($establishment->user->identification_number)) {
                $ident = $establishment->user->identification_number;
            } elseif (isset($doc->customer) && is_object($doc->customer) && isset($doc->customer->identification_number)) {
                // fallback
                $ident = $doc->customer->identification_number;
            }
            if ($ident) {
                $xmlPath = storage_path("app/public/{$ident}/" . $doc->xml);
                if (file_exists($xmlPath)) {
                    echo "XML path: {$xmlPath}\n";
                } else {
                    echo "XML path no encontrado (intentado): {$xmlPath}\n";
                }
            }
        } catch (\Throwable $t) {
            // ignore path resolution errors
        }
    } else {
        echo "Sin XML asociado.\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "=== Fin ===\n";
