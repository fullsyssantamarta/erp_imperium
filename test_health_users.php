<?php

// Simple test file to check health users data
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Tenant\TenancyHealthUser;

try {
    echo "Testing TenancyHealthUser model...\n";
    
    // Get count
    $count = TenancyHealthUser::count();
    echo "Total users: $count\n";
    
    // Get first 3
    $users = TenancyHealthUser::limit(3)->get(['id', 'documento', 'primer_nombre', 'primer_apellido']);
    echo "First 3 users:\n";
    foreach ($users as $user) {
        echo "- ID: {$user->id}, Doc: {$user->documento}, Name: {$user->primer_nombre} {$user->primer_apellido}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
