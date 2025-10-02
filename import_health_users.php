<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Tenant\TenancyHealthUser;

// Configurar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Los datos procesados del Excel
$users_data = [
    [
        'documento' => '1234567890',
        'tipo_documento' => 'CC',
        'primer_apellido' => 'GARCÍA',
        'segundo_apellido' => 'LÓPEZ',
        'primer_nombre' => 'JUAN',
        'segundo_nombre' => 'CARLOS',
        'nombre_completo' => 'GARCÍA LÓPEZ JUAN CARLOS',
        'telefono' => '1234567',
        'celular' => '3001234567',
        'email' => 'juan.garcia@email.com',
        'direccion' => 'CALLE 123 #45-67',
        'fecha_nacimiento' => '1985-03-15',
        'edad' => 38,
        'genero' => 'M',
        'estado_civil' => 'SOLTERO',
        'departamento' => 'CUNDINAMARCA',
        'municipio' => 'BOGOTÁ',
        'zona' => 'URBANA',
        'eps_codigo' => 'EPSS01',
        'eps_nombre' => 'NUEVA EPS',
        'tipo_afiliacion' => 'CONTRIBUTIVO',
        'regimen' => 'CONTRIBUTIVO',
        'grupo_poblacional' => 'ADULTO',
        'nivel_sisben' => 'NO APLICA',
        'discapacidad' => false,
        'tipo_discapacidad' => null,
        'codigo_cups' => '890201',
        'nombre_procedimiento' => 'CONSULTA MEDICINA GENERAL',
        'valor_procedimiento' => 50000.00,
        'codigo_diagnostico' => 'Z000',
        'nombre_diagnostico' => 'EXAMEN MÉDICO GENERAL',
        'observaciones' => 'PACIENTE EN BUEN ESTADO GENERAL'
    ]
    // Aquí irían más registros procesados del Excel
];

echo "Iniciando importación de usuarios del sector salud...\n";

$imported = 0;
$errors = 0;

foreach ($users_data as $user_data) {
    try {
        // Verificar si ya existe el usuario
        $existing = TenancyHealthUser::where('documento', $user_data['documento'])->first();
        
        if (!$existing) {
            TenancyHealthUser::create($user_data);
            $imported++;
            echo "✓ Importado usuario: {$user_data['documento']} - {$user_data['nombre_completo']}\n";
        } else {
            echo "- Ya existe usuario: {$user_data['documento']}\n";
        }
    } catch (Exception $e) {
        $errors++;
        echo "✗ Error importando {$user_data['documento']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESUMEN DE IMPORTACIÓN ===\n";
echo "Usuarios importados: $imported\n";
echo "Errores: $errors\n";
echo "Total procesados: " . count($users_data) . "\n";
echo "¡Importación completada!\n";
