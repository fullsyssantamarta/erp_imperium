<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Servicio Enterprise para limpieza y validación de health_fields
 * Elimina duplicaciones, consolida estructura y valida datos antes de persistir en DB
 */
class HealthFieldsCleanerService
{
    /**
     * Limpia y consolida la estructura de health_fields eliminando duplicaciones
     * 
     * @param array $rawData Datos crudos que pueden contener duplicaciones
     * @return array Estructura limpia y consolidada
     */
    public function cleanAndConsolidateHealthFields(array $rawData): array
    {
        Log::info('🧹 INICIANDO LIMPIEZA DE HEALTH_FIELDS', [
            'raw_data_keys' => array_keys($rawData),
            'has_health_fields' => isset($rawData['health_fields']) ? 'YES' : 'NO',
            'has_root_users_info' => isset($rawData['users_info']) ? 'YES' : 'NO'
        ]);

        // Estructura limpia base
        $cleanHealthFields = [];

        // === PASO 1: EXTRAER FECHAS DE PERÍODO ===
        $cleanHealthFields = $this->extractInvoicePeriod($rawData, $cleanHealthFields);

        // === PASO 2: CONSOLIDAR HEALTH_TYPE_OPERATION_ID ===
        $cleanHealthFields['health_type_operation_id'] = $this->consolidateHealthTypeOperation($rawData);

        // === PASO 3: CONSOLIDAR USERS_INFO ===
        $cleanHealthFields['users_info'] = $this->consolidateUsersInfo($rawData);

        // === PASO 4: VALIDAR ESTRUCTURA FINAL ===
        $this->validateFinalStructure($cleanHealthFields);

        Log::info('✅ LIMPIEZA DE HEALTH_FIELDS COMPLETADA', [
            'cleaned_structure' => array_keys($cleanHealthFields),
            'users_count' => count($cleanHealthFields['users_info']),
            'health_type_operation_id' => $cleanHealthFields['health_type_operation_id']
        ]);

        return $cleanHealthFields;
    }

    /**
     * Extrae y consolida las fechas del período de facturación
     */
    private function extractInvoicePeriod(array $rawData, array $cleanData): array
    {
        // Prioridad 1: Fechas explícitas en health_fields
        if (isset($rawData['health_fields']['invoice_period_start_date']) && 
            isset($rawData['health_fields']['invoice_period_end_date'])) {
            
            $cleanData['invoice_period_start_date'] = $rawData['health_fields']['invoice_period_start_date'];
            $cleanData['invoice_period_end_date'] = $rawData['health_fields']['invoice_period_end_date'];
            
            Log::info('📅 FECHAS EXTRAÍDAS DE health_fields', [
                'start_date' => $cleanData['invoice_period_start_date'],
                'end_date' => $cleanData['invoice_period_end_date']
            ]);
            
            return $cleanData;
        }

        // Prioridad 2: invoice_period string (convertir a fechas)
        if (isset($rawData['invoice_period'])) {
            $period = $rawData['invoice_period'];
            
            // Si es formato "2025-09", generar fechas
            if (preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                
                $cleanData['invoice_period_start_date'] = "$year-$month-01";
                $cleanData['invoice_period_end_date'] = date('Y-m-t', strtotime("$year-$month-01"));
                
                Log::info('📅 FECHAS GENERADAS DESDE invoice_period', [
                    'original_period' => $period,
                    'start_date' => $cleanData['invoice_period_start_date'],
                    'end_date' => $cleanData['invoice_period_end_date']
                ]);
                
                return $cleanData;
            }
        }

        // Fallback: fechas por defecto (mes actual)
        $cleanData['invoice_period_start_date'] = date('Y-m-01');
        $cleanData['invoice_period_end_date'] = date('Y-m-t');
        
        Log::warning('⚠️ FECHAS POR DEFECTO APLICADAS', [
            'start_date' => $cleanData['invoice_period_start_date'],
            'end_date' => $cleanData['invoice_period_end_date']
        ]);

        return $cleanData;
    }

    /**
     * Consolida health_type_operation_id eliminando inconsistencias
     */
    private function consolidateHealthTypeOperation(array $rawData): int
    {
        $candidates = [];

        // Buscar en health_fields (prioridad alta)
        if (isset($rawData['health_fields']['health_type_operation_id'])) {
            $candidates['health_fields'] = (int) $rawData['health_fields']['health_type_operation_id'];
        }

        // Buscar en nivel raíz (prioridad baja)
        if (isset($rawData['health_type_operation_id'])) {
            $candidates['root'] = (int) $rawData['health_type_operation_id'];
        }

        Log::info('🔄 CONSOLIDANDO HEALTH_TYPE_OPERATION_ID', [
            'candidates' => $candidates
        ]);

        // Priorizar health_fields sobre root
        if (isset($candidates['health_fields'])) {
            $finalValue = $candidates['health_fields'];
            $source = 'health_fields';
        } elseif (isset($candidates['root'])) {
            $finalValue = $candidates['root'];
            $source = 'root';
        } else {
            $finalValue = 1; // Valor por defecto para prestaciones
            $source = 'default';
        }

        // Validar valor (solo 1=Prestación o 2=Evento son válidos)
        if (!in_array($finalValue, [1, 2])) {
            Log::warning('⚠️ VALOR INVÁLIDO PARA health_type_operation_id, APLICANDO DEFECTO', [
                'invalid_value' => $finalValue,
                'corrected_to' => 1
            ]);
            $finalValue = 1;
            $source = 'corrected_default';
        }

        Log::info('✅ HEALTH_TYPE_OPERATION_ID CONSOLIDADO', [
            'final_value' => $finalValue,
            'source' => $source,
            'description' => $finalValue === 1 ? 'Prestación de servicios' : 'Evento de salud'
        ]);

        return $finalValue;
    }

    /**
     * Consolida users_info eliminando duplicaciones
     */
    private function consolidateUsersInfo(array $rawData): array
    {
        $candidates = [];

        // Buscar en health_fields (prioridad alta)
        if (isset($rawData['health_fields']['users_info']) && is_array($rawData['health_fields']['users_info'])) {
            $candidates['health_fields'] = $rawData['health_fields']['users_info'];
        }

        // Buscar en nivel raíz (prioridad baja)
        if (isset($rawData['users_info']) && is_array($rawData['users_info'])) {
            $candidates['root'] = $rawData['users_info'];
        }

        Log::info('👥 CONSOLIDANDO USERS_INFO', [
            'candidates_found' => array_keys($candidates),
            'health_fields_count' => isset($candidates['health_fields']) ? count($candidates['health_fields']) : 0,
            'root_count' => isset($candidates['root']) ? count($candidates['root']) : 0
        ]);

        // Priorizar health_fields sobre root
        $finalUsersInfo = [];
        $source = 'none';

        if (!empty($candidates['health_fields'])) {
            $finalUsersInfo = $candidates['health_fields'];
            $source = 'health_fields';
        } elseif (!empty($candidates['root'])) {
            $finalUsersInfo = $candidates['root'];
            $source = 'root';
        }

        // Limpiar y validar cada usuario
        $cleanedUsers = [];
        foreach ($finalUsersInfo as $index => $user) {
            $cleanedUser = $this->cleanUserInfo($user, $index);
            if ($cleanedUser) {
                $cleanedUsers[] = $cleanedUser;
            }
        }

        Log::info('✅ USERS_INFO CONSOLIDADO', [
            'source' => $source,
            'original_count' => count($finalUsersInfo),
            'cleaned_count' => count($cleanedUsers)
        ]);

        return $cleanedUsers;
    }

    /**
     * Limpia y valida información de un usuario individual
     */
    private function cleanUserInfo(array $user, int $index): ?array
    {
        $required = ['identification_number', 'first_name', 'surname'];
        
        // Validar campos obligatorios
        foreach ($required as $field) {
            if (empty($user[$field])) {
                Log::warning('⚠️ USUARIO INVÁLIDO - CAMPO OBLIGATORIO FALTANTE', [
                    'user_index' => $index,
                    'missing_field' => $field,
                    'user_data' => $user
                ]);
                return null;
            }
        }

        // Estructura limpia del usuario con valores por defecto
        $cleanUser = [
            'provider_code' => $user['provider_code'] ?? '1100187015',
            'health_type_document_identification_id' => (int) ($user['health_type_document_identification_id'] ?? 1),
            'identification_number' => trim($user['identification_number']),
            'surname' => trim($user['surname']),
            'second_surname' => trim($user['second_surname'] ?? ''),
            'first_name' => trim($user['first_name']),
            'middle_name' => trim($user['middle_name'] ?? ''),
            'health_type_user_id' => (int) ($user['health_type_user_id'] ?? 4),
            'health_contracting_payment_method_id' => (int) ($user['health_contracting_payment_method_id'] ?? 4),
            'health_coverage_id' => (int) ($user['health_coverage_id'] ?? 1),
            'autorization_numbers' => $user['autorization_numbers'] ?? '',
            'mipres' => $user['mipres'] ?? null,
            'mipres_delivery' => $user['mipres_delivery'] ?? null,
            'contract_number' => $user['contract_number'] ?? '',
            'policy_number' => $user['policy_number'] ?? null,
            'co_payment' => $this->normalizeNumericValue($user['co_payment'] ?? 0),
            'moderating_fee' => $this->normalizeNumericValue($user['moderating_fee'] ?? 0),
            'recovery_fee' => $this->normalizeNumericValue($user['recovery_fee'] ?? 0),
            'shared_payment' => $this->normalizeNumericValue($user['shared_payment'] ?? 0)
        ];

        return $cleanUser;
    }

    /**
     * Normaliza valores numéricos (convierte strings a números)
     */
    private function normalizeNumericValue($value): string
    {
        if (is_numeric($value)) {
            return (string) $value;
        }
        return "0";
    }

    /**
     * Valida la estructura final de health_fields
     */
    private function validateFinalStructure(array $cleanData): void
    {
        $required = ['invoice_period_start_date', 'invoice_period_end_date', 'health_type_operation_id', 'users_info'];
        
        foreach ($required as $field) {
            if (!isset($cleanData[$field])) {
                throw new \Exception("Campo obligatorio faltante en health_fields: {$field}");
            }
        }

        // Validar fechas
        if (!$this->isValidDate($cleanData['invoice_period_start_date'])) {
            throw new \Exception("Fecha de inicio inválida: {$cleanData['invoice_period_start_date']}");
        }

        if (!$this->isValidDate($cleanData['invoice_period_end_date'])) {
            throw new \Exception("Fecha de fin inválida: {$cleanData['invoice_period_end_date']}");
        }

        // Validar usuarios
        if (empty($cleanData['users_info'])) {
            throw new \Exception("users_info no puede estar vacío");
        }

        Log::info('✅ ESTRUCTURA HEALTH_FIELDS VALIDADA EXITOSAMENTE');
    }

    /**
     * Valida formato de fecha Y-m-d
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Genera estructura de ejemplo para referencia
     */
    public function getExampleStructure(): array
    {
        return [
            "invoice_period_start_date" => "2025-09-01",
            "invoice_period_end_date" => "2025-09-14",
            "health_type_operation_id" => 1,
            "users_info" => [
                [
                    "provider_code" => "1100187015",
                    "health_type_document_identification_id" => 1,
                    "identification_number" => "39520490",
                    "surname" => "GIL",
                    "second_surname" => "AVELLA",
                    "first_name" => "MARIA",
                    "middle_name" => "MERCEDES",
                    "health_type_user_id" => 4,
                    "health_contracting_payment_method_id" => 4,
                    "health_coverage_id" => 1,
                    "autorization_numbers" => "90925167",
                    "mipres" => null,
                    "mipres_delivery" => null,
                    "contract_number" => "S-13-1-08-1-04619",
                    "policy_number" => null,
                    "co_payment" => "0",
                    "moderating_fee" => "0",
                    "recovery_fee" => "0",
                    "shared_payment" => "0"
                ]
            ]
        ];
    }
}