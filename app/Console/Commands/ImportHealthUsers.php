<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\TenancyHealthUser;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ImportHealthUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:import-users {file_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar usuarios del sector salud desde Excel';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $filePath = $this->argument('file_path');
        
        if (!file_exists($filePath)) {
            $this->error("El archivo no existe: $filePath");
            return 1;
        }

        $this->info("Procesando archivo: $filePath");
        
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Obtener headers (primera fila)
            $headers = array_shift($rows);
            $this->info("Headers encontrados: " . implode(', ', $headers));
            
            $imported = 0;
            $errors = 0;
            
            foreach ($rows as $index => $row) {
                try {
                    // Mapear datos del Excel a campos de la BD
                    $userData = $this->mapExcelData($headers, $row);
                    
                    // Debug: mostrar datos de la primera fila
                    if ($index == 0) {
                        $this->info("Datos mapeados primera fila: " . json_encode($userData, JSON_UNESCAPED_UNICODE));
                    }
                    
                    if (empty($userData['numero_documento_identificacion'])) {
                        $this->line("Fila " . ($index + 2) . ": documento vacío, saltando...");
                        continue; // Saltar filas sin documento
                    }
                    
                    // Verificar si ya existe usando query directo
                    $connection = DB::connection('system');
                    $connection->statement('USE tenancy_ddb');
                    
                    $existing = $connection->table('tenancy_health_users')
                        ->where('numero_documento_identificacion', $userData['numero_documento_identificacion'])
                        ->first();
                    
                    if (!$existing) {
                        $connection->table('tenancy_health_users')->insert($userData);
                        $imported++;
                        $this->line("✓ Importado: {$userData['numero_documento_identificacion']} - {$userData['primer_nombre']} {$userData['primer_apellido']}");
                    } else {
                        $this->line("- Ya existe: {$userData['numero_documento_identificacion']}");
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Error fila " . ($index + 2) . ": " . $e->getMessage());
                }
            }
            
            $this->info("\n=== RESUMEN ===");
            $this->info("Importados: $imported");
            $this->info("Errores: $errors");
            $this->info("Total procesados: " . count($rows));
            
        } catch (\Exception $e) {
            $this->error("Error procesando el archivo: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function mapExcelData($headers, $row)
    {
        $data = array_combine($headers, $row);
        
        // Mapeo específico según las columnas del Excel real
        $documento = trim($data['NUMDOCUMENTOIDENTIFICACION'] ?? '');
        
        if (empty($documento)) {
            return ['documento' => ''];
        }
        
        return [
            'documento' => $documento,
            'tipo_documento' => $data['TIPODOCUMENTOIDENTIFICACION'] ?? 'CC',
            'primer_apellido' => $data['PRIMER_APELLIDO'] ?? '',
            'segundo_apellido' => $data['SEGUNDO_APELLIDO'] ?? '',
            'primer_nombre' => $data['PRIMER_NOMBRE'] ?? '',
            'segundo_nombre' => $data['SEGUNDO_NOMBRE'] ?? '',
            'nombre_completo' => trim(($data['PRIMER_APELLIDO'] ?? '') . ' ' . 
                                    ($data['SEGUNDO_APELLIDO'] ?? '') . ' ' . 
                                    ($data['PRIMER_NOMBRE'] ?? '') . ' ' . 
                                    ($data['SEGUNDO_NOMBRE'] ?? '')),
            'telefono' => $data['ASP_CONTACT_TELEPHONE'] ?? null,
            'celular' => $data['ASP_CONTACT_TELEPHONE'] ?? null,
            'email' => $data['ASP_CONTACT_ELECTRONICMAIL'] ?? null,
            'direccion' => $data['ASP_ADDRESSLINE'] ?? null,
            'fecha_nacimiento' => $this->parseDate($data['FECHANACIMIENTO'] ?? null),
            'edad' => $this->calculateAge($data['FECHANACIMIENTO'] ?? null),
            'genero' => $this->mapGender($data['CODSEXO'] ?? null),
            'estado_civil' => null,
            'departamento' => $data['ASP_COUNTRYSUBENTITYNAME'] ?? null,
            'municipio' => $data['ASP_CITYNAME'] ?? null,
            'zona' => $data['CODZONATERRITORIALRESIDENCIA'] ?? null,
            'eps_codigo' => $data['CODPRESTADOR'] ?? null,
            'eps_nombre' => $data['EPS'] ?? null,
            'tipo_afiliacion' => $data['TIPO_USUARIO'] ?? null,
            'regimen' => $data['MODALIDAD_CONTRATACION'] ?? null,
            'grupo_poblacional' => null,
            'nivel_sisben' => null,
            'discapacidad' => false,
            'tipo_discapacidad' => null,
            'codigo_cups' => $data['CODTECNOLOGIASALUD'] ?? null,
            'nombre_procedimiento' => $data['NOMTECNOLOGIASALUD'] ?? null,
            'valor_procedimiento' => floatval($data['PRODUCTVALUE'] ?? 0),
            'codigo_diagnostico' => $data['CODDIAGNOSTICOPRINCIPAL'] ?? null,
            'nombre_diagnostico' => null,
            'observaciones' => $data['ITEMDESCRIPTION'] ?? null
        ];
    }
    
    private function parseDate($date)
    {
        if (empty($date)) return null;
        
        try {
            if (is_numeric($date)) {
                // Excel date serial number
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function calculateAge($birthDate)
    {
        if (empty($birthDate)) return null;
        
        try {
            $parsedDate = $this->parseDate($birthDate);
            if ($parsedDate) {
                $today = new \DateTime();
                $birth = new \DateTime($parsedDate);
                return $today->diff($birth)->y;
            }
        } catch (\Exception $e) {
            // Ignore error
        }
        
        return null;
    }
    
    private function mapGender($gender)
    {
        if (empty($gender)) return null;
        
        $gender = strtoupper(trim($gender));
        switch ($gender) {
            case 'M':
            case 'MASCULINO':
            case '1':
                return 'M';
            case 'F':
            case 'FEMENINO':
            case '2':
                return 'F';
            default:
                return null;
        }
    }
}
