<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HealthFieldsValidatorService;
use Exception;

class TestHealthValidation extends Command
{
    protected $signature = 'health:test-validation';
    protected $description = 'Test health fields validation service';

    public function handle()
    {
        $this->info('🏥 === TESTING HEALTH FIELDS VALIDATOR ===');
        
        try {
            $validator = app(HealthFieldsValidatorService::class);
            
            // Test data estructura válida
            $testHealthFields = [
                'invoice_period_start_date' => '2025-01-01',
                'invoice_period_end_date' => '2025-01-31',
                'health_type_operation_id' => 1,
                'users_info' => [
                    [
                        'user_type_document_id' => 1,
                        'user_document_number' => '12345678',
                        'user_first_name' => 'Juan',
                        'user_last_name' => 'Pérez',
                        'user_contract_code' => 'CON001',
                        'user_payment_code' => '01'
                    ]
                ]
            ];
            
            $this->info('📋 Testing input data:');
            $this->line(json_encode($testHealthFields, JSON_PRETTY_PRINT));
            
            $result = $validator->validateAndTransform($testHealthFields);
            
            $this->info('✅ VALIDATION SUCCESS');
            $this->info('📋 Transformed structure:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('❌ VALIDATION ERROR: ' . $e->getMessage());
            $this->error('Line: ' . $e->getLine());
            return Command::FAILURE;
        }
    }
}