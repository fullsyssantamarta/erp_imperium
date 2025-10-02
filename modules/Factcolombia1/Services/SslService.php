<?php

namespace Modules\Factcolombia1\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class SslService
{
    /**
     * Generar certificado SSL para un subdominio
     * 
     * @param string $subdomain
     * @return array
     */
    public static function generateSslForSubdomain($subdomain)
    {
        try {
            // Validar el subdominio
            if (empty($subdomain) || !preg_match('/^[a-z0-9]+$/i', $subdomain)) {
                throw new Exception('Subdominio inválido: ' . $subdomain);
            }

            $fullDomain = $subdomain . '.imperiumfevsrips.net';
            $certPath = '/root/setup/certs/' . $fullDomain . '.crt';
            
            // Verificar si el certificado ya existe
            if (file_exists($certPath)) {
                Log::info("SSL ya existe para: {$fullDomain}");
                return [
                    'success' => true,
                    'message' => "Certificado SSL ya existe para {$fullDomain}",
                    'domain' => $fullDomain,
                    'existing' => true
                ];
            }

            // Ejecutar el script de generación SSL
            $scriptPath = '/root/generate_ssl_subdomain.sh';
            $command = "sudo {$scriptPath} {$subdomain} 2>&1";
            
            Log::info("Ejecutando comando SSL: {$command}");
            
            $output = shell_exec($command);
            $exitCode = shell_exec("echo $?");
            
            Log::info("Salida del comando SSL: {$output}");
            Log::info("Código de salida: {$exitCode}");
            
            // Verificar si se generó el certificado
            if (trim($exitCode) == '0' && file_exists($certPath)) {
                Log::info("SSL generado exitosamente para: {$fullDomain}");
                
                return [
                    'success' => true,
                    'message' => "Certificado SSL generado exitosamente para {$fullDomain}",
                    'domain' => $fullDomain,
                    'cert_path' => $certPath,
                    'output' => $output
                ];
            } else {
                throw new Exception("Error al generar certificado SSL. Salida: {$output}");
            }
            
        } catch (Exception $e) {
            Log::error("Error generando SSL para {$subdomain}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al generar certificado SSL: ' . $e->getMessage(),
                'domain' => $subdomain . '.imperiumfevsrips.net',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si existe certificado SSL para un dominio
     * 
     * @param string $domain
     * @return bool
     */
    public static function sslExists($domain)
    {
        $certPath = '/root/setup/certs/' . $domain . '.crt';
        return file_exists($certPath);
    }

    /**
     * Obtener información del certificado SSL
     * 
     * @param string $domain
     * @return array
     */
    public static function getSslInfo($domain)
    {
        $certPath = '/root/setup/certs/' . $domain . '.crt';
        
        if (!file_exists($certPath)) {
            return [
                'exists' => false,
                'message' => 'Certificado no encontrado'
            ];
        }

        try {
            $certData = openssl_x509_parse(file_get_contents($certPath));
            
            return [
                'exists' => true,
                'valid_from' => date('Y-m-d H:i:s', $certData['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $certData['validTo_time_t']),
                'issuer' => $certData['issuer']['CN'] ?? 'Desconocido',
                'subject' => $certData['subject']['CN'] ?? 'Desconocido'
            ];
        } catch (Exception $e) {
            return [
                'exists' => true,
                'error' => 'Error al leer certificado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Renovar certificado SSL
     * 
     * @param string $subdomain
     * @return array
     */
    public static function renewSsl($subdomain)
    {
        try {
            $fullDomain = $subdomain . '.imperiumfevsrips.net';
            $command = "sudo certbot renew --cert-name {$fullDomain} 2>&1";
            
            Log::info("Renovando SSL para: {$fullDomain}");
            
            $output = shell_exec($command);
            
            Log::info("Salida de renovación SSL: {$output}");
            
            return [
                'success' => true,
                'message' => "Renovación de SSL procesada para {$fullDomain}",
                'output' => $output
            ];
            
        } catch (Exception $e) {
            Log::error("Error renovando SSL para {$subdomain}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al renovar certificado SSL: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}
