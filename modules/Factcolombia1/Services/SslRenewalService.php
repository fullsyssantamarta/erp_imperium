<?php

namespace Modules\Factcolombia1\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SslRenewalService
{
    /**
     * Verificar estado de renovación de todos los certificados
     * 
     * @return array
     */
    public static function checkRenewalStatus()
    {
        $certDir = '/root/setup/certs';
        $certificates = [];
        
        try {
            $certFiles = glob($certDir . '/*.crt');
            
            foreach ($certFiles as $certFile) {
                $domain = basename($certFile, '.crt');
                $certInfo = self::getCertificateInfo($certFile);
                
                $certificates[] = [
                    'domain' => $domain,
                    'cert_file' => $certFile,
                    'valid_from' => $certInfo['valid_from'],
                    'valid_to' => $certInfo['valid_to'],
                    'days_until_expiry' => $certInfo['days_until_expiry'],
                    'needs_renewal' => $certInfo['days_until_expiry'] < 30,
                    'urgent_renewal' => $certInfo['days_until_expiry'] < 7,
                    'status' => self::getRenewalStatus($certInfo['days_until_expiry'])
                ];
            }
            
            return [
                'success' => true,
                'certificates' => $certificates,
                'total_certificates' => count($certificates),
                'needs_renewal' => array_filter($certificates, function($cert) {
                    return $cert['needs_renewal'];
                }),
                'urgent_renewal' => array_filter($certificates, function($cert) {
                    return $cert['urgent_renewal'];
                })
            ];
            
        } catch (Exception $e) {
            Log::error("Error verificando estado de renovación SSL: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'certificates' => []
            ];
        }
    }

    /**
     * Obtener información de un certificado
     * 
     * @param string $certFile
     * @return array
     */
    private static function getCertificateInfo($certFile)
    {
        try {
            $certData = openssl_x509_parse(file_get_contents($certFile));
            
            $validFrom = Carbon::createFromTimestamp($certData['validFrom_time_t']);
            $validTo = Carbon::createFromTimestamp($certData['validTo_time_t']);
            $daysUntilExpiry = Carbon::now()->diffInDays($validTo, false);
            
            return [
                'valid_from' => $validFrom->format('Y-m-d H:i:s'),
                'valid_to' => $validTo->format('Y-m-d H:i:s'),
                'days_until_expiry' => $daysUntilExpiry,
                'issuer' => $certData['issuer']['CN'] ?? 'Desconocido',
                'subject' => $certData['subject']['CN'] ?? 'Desconocido'
            ];
        } catch (Exception $e) {
            return [
                'valid_from' => 'Error',
                'valid_to' => 'Error',
                'days_until_expiry' => 0,
                'issuer' => 'Error',
                'subject' => 'Error'
            ];
        }
    }

    /**
     * Obtener estado de renovación basado en días restantes
     * 
     * @param int $daysUntilExpiry
     * @return string
     */
    private static function getRenewalStatus($daysUntilExpiry)
    {
        if ($daysUntilExpiry < 0) {
            return 'expired';
        } elseif ($daysUntilExpiry < 7) {
            return 'urgent';
        } elseif ($daysUntilExpiry < 30) {
            return 'warning';
        } else {
            return 'valid';
        }
    }

    /**
     * Ejecutar renovación manual de certificados
     * 
     * @return array
     */
    public static function runManualRenewal()
    {
        try {
            Log::info("Iniciando renovación manual de certificados SSL");
            
            $command = "sudo certbot renew --force-renewal --post-hook 'docker restart proxy' 2>&1";
            $output = shell_exec($command);
            $exitCode = shell_exec("echo $?");
            
            Log::info("Salida de renovación manual: {$output}");
            
            if (trim($exitCode) == '0') {
                Log::info("Renovación manual completada exitosamente");
                
                return [
                    'success' => true,
                    'message' => 'Renovación de certificados completada exitosamente',
                    'output' => $output
                ];
            } else {
                throw new Exception("Error en renovación manual. Código: {$exitCode}, Salida: {$output}");
            }
            
        } catch (Exception $e) {
            Log::error("Error en renovación manual SSL: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error en renovación manual: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener logs de renovación
     * 
     * @param int $lines
     * @return array
     */
    public static function getRenewalLogs($lines = 50)
    {
        try {
            $logFile = '/var/log/ssl-renewal.log';
            
            if (!file_exists($logFile)) {
                return [
                    'success' => true,
                    'logs' => ['No hay logs de renovación disponibles'],
                    'message' => 'Archivo de log no encontrado'
                ];
            }
            
            $command = "tail -n {$lines} {$logFile}";
            $output = shell_exec($command);
            
            $logs = $output ? explode("\n", trim($output)) : [];
            
            return [
                'success' => true,
                'logs' => $logs,
                'log_file' => $logFile,
                'lines_requested' => $lines
            ];
            
        } catch (Exception $e) {
            Log::error("Error obteniendo logs de renovación: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => []
            ];
        }
    }

    /**
     * Verificar si el servicio de renovación automática está activo
     * 
     * @return array
     */
    public static function checkCronStatus()
    {
        try {
            $cronOutput = shell_exec("crontab -l 2>/dev/null | grep ssl_renewal.sh");
            
            $isActive = !empty(trim($cronOutput));
            
            return [
                'success' => true,
                'cron_active' => $isActive,
                'cron_entry' => trim($cronOutput) ?: 'No configurado',
                'message' => $isActive ? 'Renovación automática activa' : 'Renovación automática no configurada'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'cron_active' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener resumen del estado SSL del sistema
     * 
     * @return array
     */
    public static function getSystemSslSummary()
    {
        $renewalStatus = self::checkRenewalStatus();
        $cronStatus = self::checkCronStatus();
        
        $summary = [
            'ssl_auto_enabled' => config('app.auto_ssl_enabled', false),
            'total_certificates' => $renewalStatus['total_certificates'] ?? 0,
            'valid_certificates' => 0,
            'warning_certificates' => 0,
            'urgent_certificates' => 0,
            'expired_certificates' => 0,
            'auto_renewal_active' => $cronStatus['cron_active'] ?? false,
            'last_check' => Carbon::now()->format('Y-m-d H:i:s')
        ];
        
        if ($renewalStatus['success']) {
            foreach ($renewalStatus['certificates'] as $cert) {
                switch ($cert['status']) {
                    case 'valid':
                        $summary['valid_certificates']++;
                        break;
                    case 'warning':
                        $summary['warning_certificates']++;
                        break;
                    case 'urgent':
                        $summary['urgent_certificates']++;
                        break;
                    case 'expired':
                        $summary['expired_certificates']++;
                        break;
                }
            }
        }
        
        return $summary;
    }
}
