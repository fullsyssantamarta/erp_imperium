<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Validador y Transformador de Health Fields para APIDIAN 2025
 * 
 * Esta clase garantiza que los datos de salud cumplan con:
 * - Estructura exacta requerida por APIDIAN 2025
 * - Validaciones RIPS del Ministerio de Salud
 * - Formato UBL 2.1 para facturas electrónicas de salud
 */
class HealthFieldsValidatorService
{
    /**
     * Estructura válida para APIDIAN 2025 según UBL 2.1
     */
    private const REQUIRED_HEALTH_STRUCTURE = [
        'invoice_period_start_date' => 'required|date',
        'invoice_period_end_date' => 'required|date|after_or_equal:invoice_period_start_date',
        'health_type_operation_id' => 'required|integer|between:1,2',
        'users_info' => 'required|array|min:1'
    ];

    /**
     * Estructura válida para users_info según RIPS
     */
    private const USER_INFO_STRUCTURE = [
        'user_type_document_id' => 'required|integer',
        'user_document_number' => 'required|string|max:20',
        'user_first_name' => 'required|string|max:60',
        'user_last_name' => 'required|string|max:60',
        'user_contract_code' => 'required|string|max:15',
        'user_payment_code' => 'required|string|max:2'
    ];

    /**
     * Validar y transformar health_fields para APIDIAN 2025
     *
     * @param array $health_fields
     * @return array
     * @throws Exception
     */
    public function validateAndTransform(array $health_fields): array
    {
        try {
            Log::info('HEALTH VALIDATOR - Input data:', $health_fields);

            // 1. Validar estructura principal
            $this->validateRequiredFields($health_fields);

            // 2. Validar y transformar fechas
            $transformedData = $this->transformHealthFields($health_fields);

            // 3. Validar usuarios de salud
            $transformedData['users_info'] = $this->validateUsersInfo($health_fields['users_info']);

            // 4. Aplicar estructura APIDIAN 2025
            $apidianStructure = $this->applyApidianStructure($transformedData);

            Log::info('HEALTH VALIDATOR - Transformed data:', $apidianStructure);

            return $apidianStructure;

        } catch (Exception $e) {
            Log::error('HEALTH VALIDATOR - Error:', [
                'message' => $e->getMessage(),
                'input' => $health_fields,
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Validar campos requeridos
     */
    private function validateRequiredFields(array $health_fields): void
    {
        $missing = [];

        foreach (self::REQUIRED_HEALTH_STRUCTURE as $field => $rule) {
            if (strpos($rule, 'required') !== false && !isset($health_fields[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new Exception("Campos requeridos faltantes en health_fields: " . implode(', ', $missing));
        }
    }

    /**
     * Transformar campos de salud según APIDIAN 2025
     */
    private function transformHealthFields(array $health_fields): array
    {
        $transformed = [];

        // Fechas en formato ISO 8601 requerido por APIDIAN
        $transformed['invoice_period_start_date'] = $this->formatDateForApidian($health_fields['invoice_period_start_date']);
        $transformed['invoice_period_end_date'] = $this->formatDateForApidian($health_fields['invoice_period_end_date']);

        // Validar período de facturación
        if ($transformed['invoice_period_start_date'] > $transformed['invoice_period_end_date']) {
            throw new Exception("La fecha de inicio no puede ser posterior a la fecha de fin del período de facturación");
        }

        // Tipo de operación de salud
        $transformed['health_type_operation_id'] = (int) ($health_fields['health_type_operation_id'] ?? 1);

        if (!in_array($transformed['health_type_operation_id'], [1, 2])) {
            throw new Exception("health_type_operation_id debe ser 1 (Prestación) o 2 (Evento)");
        }

        return $transformed;
    }

    /**
     * Formatear fecha para APIDIAN (ISO 8601)
     */
    private function formatDateForApidian(string $date): string
    {
        try {
            $dateTime = new \DateTime($date);
            return $dateTime->format('Y-m-d');
        } catch (Exception $e) {
            throw new Exception("Fecha inválida: {$date}. Use formato YYYY-MM-DD");
        }
    }

    /**
     * Validar información de usuarios de salud
     */
    private function validateUsersInfo(array $users_info): array
    {
        if (empty($users_info)) {
            throw new Exception("users_info no puede estar vacío para facturas de salud");
        }

        $validatedUsers = [];

        foreach ($users_info as $index => $user) {
            // Transformar estructura Vue.js a estructura APIDIAN si es necesario
            $transformedUser = $this->transformUserStructure($user, $index);
            $validatedUser = $this->validateSingleUser($transformedUser, $index);
            $validatedUsers[] = $validatedUser;
        }

        return $validatedUsers;
    }

    /**
     * Transformar estructura de usuario de Vue.js a APIDIAN
     */
    private function transformUserStructure(array $user, int $index): array
    {
        Log::info("HEALTH VALIDATOR - Raw user data from frontend:", [
            'index' => $index,
            'user_data' => $user,
            'user_keys' => array_keys($user)
        ]);

        // Si ya tiene la estructura APIDIAN, devolverla tal como está
        if (isset($user['user_type_document_id']) && isset($user['user_document_number'])) {
            Log::info("HEALTH VALIDATOR - User already in APIDIAN format", ['index' => $index]);
            return $user;
        }

        // Transformar de estructura Vue.js (health_users.vue) a estructura APIDIAN
        if (isset($user['health_type_document_identification_id']) || isset($user['identification_number'])) {
            Log::info("HEALTH VALIDATOR - Transforming Vue.js structure to APIDIAN", ['index' => $index]);
            
            // WORKAROUND: APIDIAN tiene bug buscando modelo HealthContractingPaymentMethod
            // Usar códigos seguros que no desencadenen el error
            $paymentMethodId = (int) ($user['health_contracting_payment_method_id'] ?? 1);
            $safePaymentCodes = [
                1 => '01', // Pago individual - SEGURO
                2 => '01', // Usar código 01 como fallback
                3 => '01', // Usar código 01 como fallback  
                4 => '01', // WORKAROUND: Evitar 04 que causa error en APIDIAN
                5 => '01'  // Usar código 01 como fallback
            ];
            
            $transformedUser = [
                'user_type_document_id' => (int) ($user['health_type_document_identification_id'] ?? 1),
                'user_document_number' => (string) ($user['identification_number'] ?? ''),
                'user_first_name' => (string) ($user['first_name'] ?? ''),
                'user_last_name' => (string) ($user['surname'] ?? ''),
                'user_contract_code' => (string) ($user['contract_number'] ?? ($user['provider_code'] ?? 'DEFAULT')),
                'user_payment_code' => $safePaymentCodes[$paymentMethodId] ?? '01' // Código seguro
            ];

            // Combinar nombres si existe middle_name
            if (!empty($user['middle_name'])) {
                $transformedUser['user_first_name'] = trim($user['first_name'] . ' ' . $user['middle_name']);
            }

            // Combinar apellidos si existe second_surname  
            if (!empty($user['second_surname'])) {
                $transformedUser['user_last_name'] = trim($user['surname'] . ' ' . $user['second_surname']);
            }

            Log::info("HEALTH VALIDATOR - Vue.js transformation complete:", [
                'index' => $index,
                'original_keys' => array_keys($user),
                'transformed' => $transformedUser
            ]);

            return $transformedUser;
        }

        // Transformar desde base de datos RIPS (tenancy_health_users) a estructura APIDIAN
        if (isset($user['documento']) || isset($user['primer_nombre'])) {
            Log::info("HEALTH VALIDATOR - Transforming RIPS DB structure to APIDIAN", ['index' => $index]);
            
            $transformedUser = [
                'user_type_document_id' => $this->mapDocumentType($user['tipo_documento'] ?? 'CC'),
                'user_document_number' => (string) ($user['documento'] ?? ''),
                'user_first_name' => trim(($user['primer_nombre'] ?? '') . ' ' . ($user['segundo_nombre'] ?? '')),
                'user_last_name' => trim(($user['primer_apellido'] ?? '') . ' ' . ($user['segundo_apellido'] ?? '')),
                'user_contract_code' => (string) ($user['prestador_codigo'] ?? 'DEFAULT'),
                'user_payment_code' => '01' // Default para usuarios desde BD
            ];

            Log::info("HEALTH VALIDATOR - RIPS DB transformation complete:", [
                'index' => $index,
                'original_keys' => array_keys($user),
                'transformed' => $transformedUser
            ]);

            return $transformedUser;
        }

        // Si no tiene ninguna estructura conocida, lanzar excepción con información detallada
        Log::error("HEALTH VALIDATOR - Unknown user structure:", [
            'index' => $index,
            'user_keys' => array_keys($user),
            'user_data_sample' => array_slice($user, 0, 5, true)
        ]);
        
        throw new Exception("Usuario {$index}: Estructura de datos no reconocida. Llaves encontradas: " . implode(', ', array_keys($user)));
    }

    /**
     * Mapear tipos de documento de RIPS a IDs APIDIAN
     */
    private function mapDocumentType(string $tipoDocumento): int
    {
        $mapping = [
            'CC' => 1,  // Cédula de ciudadanía
            'CE' => 2,  // Cédula de extranjería  
            'TI' => 3,  // Tarjeta de identidad
            'PA' => 4,  // Pasaporte
            'RC' => 5,  // Registro civil
            'MS' => 6,  // Menor sin identificación
            'AS' => 7,  // Adulto sin identificación
        ];

        return $mapping[$tipoDocumento] ?? 1; // Default: CC
    }

    /**
     * Validar usuario individual
     */
    private function validateSingleUser(array $user, int $index): array
    {
        $missing = [];

        foreach (self::USER_INFO_STRUCTURE as $field => $rule) {
            if (strpos($rule, 'required') !== false && !isset($user[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new Exception("Usuario {$index}: Campos requeridos faltantes: " . implode(', ', $missing));
        }

        // Validaciones específicas
        return [
            'user_type_document_id' => (int) $user['user_type_document_id'],
            'user_document_number' => trim($user['user_document_number']),
            'user_first_name' => trim($user['user_first_name']),
            'user_last_name' => trim($user['user_last_name']),
            'user_contract_code' => trim($user['user_contract_code']),
            'user_payment_code' => trim($user['user_payment_code'])
        ];
    }

    /**
     * Aplicar estructura exacta requerida por APIDIAN 2025
     */
    private function applyApidianStructure(array $transformedData): array
    {
        // Estructura específica para APIDIAN 2025 UBL 2.1
        return [
            'health_type_operation_id' => $transformedData['health_type_operation_id'],
            'invoice_period_start_date' => $transformedData['invoice_period_start_date'],
            'invoice_period_end_date' => $transformedData['invoice_period_end_date'],
            'users_info' => $transformedData['users_info']
        ];
    }

    /**
     * Validar que la estructura sea compatible con RIPS
     */
    public function validateRipsCompliance(array $health_fields): bool
    {
        try {
            // Validaciones RIPS específicas
            $startDate = new \DateTime($health_fields['invoice_period_start_date']);
            $endDate = new \DateTime($health_fields['invoice_period_end_date']);
            
            // Período máximo permitido: 1 mes
            $interval = $startDate->diff($endDate);
            if ($interval->days > 31) {
                throw new Exception("El período de facturación no puede exceder 31 días según normativa RIPS");
            }

            // Validar usuarios contra base de datos
            foreach ($health_fields['users_info'] as $user) {
                if (!$this->validateUserExists($user['user_document_number'])) {
                    Log::warning("Usuario no encontrado en base de datos RIPS: " . $user['user_document_number']);
                }
            }

            return true;

        } catch (Exception $e) {
            Log::error('RIPS Validation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si usuario existe en base de datos
     */
    private function validateUserExists(string $documentNumber): bool
    {
        // Implementar verificación contra tenancy_health_users
        try {
            return \DB::connection('tenant')
                ->table('tenancy_health_users')
                ->where('user_document_number', $documentNumber)
                ->exists();
        } catch (Exception $e) {
            Log::warning("Error verificando usuario en BD: " . $e->getMessage());
            return true; // No bloquear por errores de BD
        }
    }
}