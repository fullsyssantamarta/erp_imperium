<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HealthFieldsValidatorService;
use Illuminate\Support\Facades\DB;
use Exception;

class TestHealthValidationWithRealData extends Command
{
    protected $signature = 'health:test-real-data';
    protected $description = 'Test health fields validation with real database data';

    public function handle()
    {
        $this->info('🏥 === TESTING HEALTH VALIDATION WITH REAL DATA ===');
        
        try {
            // Obtener datos reales de usuarios de salud
            $realUsers = DB::connection('tenant')->table('tenancy_health_users')
                ->limit(2)
                ->get()
                ->toArray();

            if (empty($realUsers)) {
                $this->error('❌ No hay usuarios de salud en la base de datos');
                return Command::FAILURE;
            }

            $this->info('📋 Found ' . count($realUsers) . ' real users in database');

            // Convertir a array para el validador
            $usersArray = array_map(function($user) {
                return (array) $user;
            }, $realUsers);

            $validator = app(HealthFieldsValidatorService::class);
            
            // Test data con usuarios reales
            $testHealthFields = [
                'invoice_period_start_date' => '2025-01-01',
                'invoice_period_end_date' => '2025-01-31',
                'health_type_operation_id' => 1,
                'users_info' => $usersArray
            ];
            
            $this->info('📋 Testing with real database users:');
            foreach ($usersArray as $index => $user) {
                $this->line("  User {$index}: {$user['documento']} - {$user['nombre_completo']}");
            }
            
            $result = $validator->validateAndTransform($testHealthFields);
            
            $this->info('✅ VALIDATION SUCCESS WITH REAL DATA');
            $this->info('📋 Transformed users:');
            foreach ($result['users_info'] as $index => $user) {
                $this->line("  User {$index}: {$user['user_document_number']} - {$user['user_first_name']} {$user['user_last_name']}");
            }
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('❌ VALIDATION ERROR: ' . $e->getMessage());
            $this->error('Line: ' . $e->getLine());
            $this->error('File: ' . $e->getFile());
            return Command::FAILURE;
        }
    }
}