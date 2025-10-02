<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para determinar el endpoint correcto de APIDIAN según el tipo de documento
 * 
 * Esta clase implementa la lógica de endpoint selection para diferentes tipos
 * de documentos electrónicos según la especificación APIDIAN 2025
 */
class ApidianEndpointResolverService
{
    /**
     * Endpoints conocidos para diferentes tipos de documentos
     */
    private const ENDPOINT_MAPPING = [
        // Facturas (estándar y de salud usan el mismo endpoint)
        'standard_invoice' => 'ubl2.1/invoice',
        
        // Otros tipos de documentos
        'credit_note' => 'ubl2.1/credit-note',
        'debit_note' => 'ubl2.1/debit-note',
        'payroll' => 'ubl2.1/payroll',
        
        // Documentos soporte
        'support_document' => 'ubl2.1/support-document'
    ];

    /**
     * Resolver endpoint correcto según el tipo de documento
     */
    public function resolveEndpoint(array $documentData, string $baseUrl, string $environment, ?string $testId = null): array
    {
        $isTestEnvironment = $environment === 'test' && !empty($testId) && $testId !== 'no_test_set_id';
        
        // Determinar tipo de documento ANTES de verificar health fields
        $documentType = $this->getDocumentType($documentData);
        
        // CORRECCIÓN: Las facturas de salud usan el mismo endpoint que facturas normales
        // No existe /health-invoice en APIDIAN, solo /invoice con health_fields en payload
        if ($documentType === 'invoice') {
            // Facturas (incluyendo facturas de salud) van a /invoice
            return $this->resolveStandardEndpoint('standard_invoice', $baseUrl, $isTestEnvironment, $testId);
        }
        
        // Notas crédito y débito
        if ($documentType === 'credit_note') {
            return $this->resolveStandardEndpoint('credit_note', $baseUrl, $isTestEnvironment, $testId);
        }
        
        if ($documentType === 'debit_note') {
            return $this->resolveStandardEndpoint('debit_note', $baseUrl, $isTestEnvironment, $testId);
        }
        
        // Documentos soporte
        if ($documentType === 'support_document') {
            return $this->resolveStandardEndpoint('support_document', $baseUrl, $isTestEnvironment, $testId);
        }
        
        // Nómina
        if ($documentType === 'payroll') {
            return $this->resolveStandardEndpoint('payroll', $baseUrl, $isTestEnvironment, $testId);
        }
        
        // Fallback a factura estándar si no se puede determinar el tipo
        return $this->resolveStandardEndpoint('standard_invoice', $baseUrl, $isTestEnvironment, $testId);
    }

    /**
     * Determinar el tipo de documento basado en los datos
     */
    private function getDocumentType(array $documentData): string
    {
        // PRIORIDAD 1: Verificar type_document_id ANTES que health_fields
        if (isset($documentData['type_document_id'])) {
            $typeId = $documentData['type_document_id'];
            
            // Mapeo según IDs comunes en Pro2
            switch ($typeId) {
                case 1:
                case '1':
                    return 'invoice';
                case 4:
                case '4':
                    return 'credit_note';
                case 5:
                case '5':
                    return 'debit_note';
                case 20:
                case '20':
                    return 'support_document';
                case 102:
                case '102':
                    return 'payroll';
                default:
                    return 'invoice'; // Default a factura
            }
        }
        
        // PRIORIDAD 2: Verificar document_type_code si existe
        if (isset($documentData['document_type_code'])) {
            $typeCode = $documentData['document_type_code'];
            
            switch ($typeCode) {
                case 1:
                case '1':
                    return 'invoice';
                case 4:
                case '4':
                    return 'credit_note';
                case 5:
                case '5':
                    return 'debit_note';
                default:
                    return 'invoice';
            }
        }
        
        // Verificar por prefijo o número de documento
        if (isset($documentData['prefix'])) {
            $prefix = strtoupper($documentData['prefix']);
            
            // Patrones comunes de prefijos
            if (strpos($prefix, 'NC') === 0 || strpos($prefix, 'NOTA') !== false) {
                return 'credit_note';
            }
            if (strpos($prefix, 'ND') === 0 || strpos($prefix, 'DEBITO') !== false) {
                return 'debit_note';
            }
            if (strpos($prefix, 'DS') === 0 || strpos($prefix, 'SOPORTE') !== false) {
                return 'support_document';
            }
        }
        
        // Default: asumir factura estándar
        return 'invoice';
    }

    /**
     * Resolver endpoint estándar para tipos de documento específicos
     */
    private function resolveStandardEndpoint(string $documentType, string $baseUrl, bool $isTestEnvironment, ?string $testId = null): array
    {
        $endpoint = self::ENDPOINT_MAPPING[$documentType];
        
        if ($isTestEnvironment && $testId) {
            $endpoint .= "/{$testId}";
        }
        
        return [
            'endpoint' => $endpoint,
            'full_url' => $baseUrl . $endpoint,
            'type' => $documentType,
            'fallback_endpoints' => []
        ];
    }

    /**
     * Obtener siguiente endpoint de fallback cuando el principal falla
     */
    public function getNextFallbackEndpoint(array $endpointInfo, int $attemptNumber = 1): ?array
    {
        if (!isset($endpointInfo['fallback_endpoints']) || empty($endpointInfo['fallback_endpoints'])) {
            return null;
        }

        $fallbacks = $endpointInfo['fallback_endpoints'];
        $index = $attemptNumber - 1;

        if ($index >= count($fallbacks)) {
            return null;
        }

        return $fallbacks[$index];
    }

    /**
     * Validar que el endpoint sea válido según documentación APIDIAN
     */
    public function validateEndpoint(string $endpoint): bool
    {
        $validPatterns = [
            '/^ubl2\.1\/invoice(\/.+)?$/',           // Facturas estándar y de salud
            '/^ubl2\.1\/credit-note(\/.+)?$/',       // Notas crédito
            '/^ubl2\.1\/debit-note(\/.+)?$/',        // Notas débito
            '/^ubl2\.1\/payroll(\/.+)?$/',           // Nómina
            '/^ubl2\.1\/support-document(\/.+)?$/'   // Documentos soporte
        ];

        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $endpoint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log detallado de resolución de endpoint
     */
    public function logEndpointResolution(array $endpointInfo, string $correlative): void
    {
        Log::info('APIDIAN ENDPOINT RESOLUTION', [
            'correlative' => $correlative,
            'primary_endpoint' => $endpointInfo['endpoint'],
            'full_url' => $endpointInfo['full_url'],
            'document_type' => $endpointInfo['type'],
            'fallback_count' => count($endpointInfo['fallback_endpoints'] ?? []),
            'fallback_endpoints' => array_column($endpointInfo['fallback_endpoints'] ?? [], 'endpoint')
        ]);
    }
}