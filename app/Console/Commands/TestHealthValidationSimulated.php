<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HealthFieldsValidatorService;
use Exception;

class TestHealthValidationSimulated extends Command
{
    protected $signature = 'health:test-simulated';
    protected $description = 'Test health fields validation with simulated Vue.js data';

    public function handle()
    {
        $this->info('🏥 === TESTING HEALTH VALIDATION WITH SIMULATED VUE.JS DATA ===');
        
        try {
            $validator = app(HealthFieldsValidatorService::class);
            
            // Simular datos que vienen desde health_users.vue (estructura real)
            $simulatedHealthFields = [
                'invoice_period_start_date' => '2025-01-01',
                'invoice_period_end_date' => '2025-01-31',
                'health_type_operation_id' => 1,
                'users_info' => [
                    [
                        // Estructura que envía health_users.vue
                        'provider_code' => 'PROV001',
                        'health_type_document_identification_id' => 1,
                        'identification_number' => '12345678',
                        'first_name' => 'JUAN',
                        'middle_name' => 'CARLOS',
                        'surname' => 'PEREZ',
                        'second_surname' => 'GARCIA',
                        'health_type_user_id' => 1,
                        'health_contracting_payment_method_id' => 1,
                        'health_coverage_id' => 1,
                        'autorization_numbers' => 'AUTH123',
                        'contract_number' => 'CON001',
                        'policy_number' => 'POL001',
                        'co_payment' => 0,
                        'moderating_fee' => 0,
                        'recovery_fee' => 0,
                        'shared_payment' => 0
                    ],
                    [
                        // Segundo usuario con estructura completa
                        'provider_code' => 'PROV002',
                        'health_type_document_identification_id' => 2,
                        'identification_number' => '87654321',
                        'first_name' => 'MARIA',
                        'middle_name' => 'JOSE',
                        'surname' => 'RODRIGUEZ',
                        'second_surname' => 'LOPEZ',
                        'health_type_user_id' => 2,
                        'health_contracting_payment_method_id' => 2,
                        'health_coverage_id' => 2,
                        'autorization_numbers' => 'AUTH456',
                        'contract_number' => 'CON002',
                        'policy_number' => 'POL002',
                        'co_payment' => 5000,
                        'moderating_fee' => 3000,
                        'recovery_fee' => 2000,
                        'shared_payment' => 1000
                    ]
                ]
            ];
            
            $this->info('📋 Testing with simulated Vue.js data structure:');
            foreach ($simulatedHealthFields['users_info'] as $index => $user) {
                $this->line("  User {$index}: {$user['identification_number']} - {$user['first_name']} {$user['surname']}");
            }
            
            $result = $validator->validateAndTransform($simulatedHealthFields);
            
            $this->info('✅ VALIDATION SUCCESS WITH SIMULATED DATA');
            $this->info('📋 Transformed structure for APIDIAN:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return 0; // Success
            
        } catch (Exception $e) {
            $this->error('❌ VALIDATION ERROR: ' . $e->getMessage());
            $this->error('Line: ' . $e->getLine());
            $this->error('File: ' . $e->getFile());
            return 1; // Failure
        }
    }
}