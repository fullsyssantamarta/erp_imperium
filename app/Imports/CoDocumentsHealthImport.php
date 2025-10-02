<?php

namespace App\Imports;

use App\Models\Tenant\Document;
use App\Models\Tenant\Person;
use App\Models\Tenant\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Modules\Factcolombia1\Models\Tenant\PaymentForm;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Factcolombia1\Http\Requests\Tenant\DocumentRequest;
use Modules\Factcolombia1\Models\SystemService\Municipality;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Importador especializado para facturas del sector salud en Colombia
 * Soporta el formato DDB_DOCUMENTO_CAMPOS_DQ con agrupación automática de productos
 * por factura y manejo completo de retenciones del sector salud
 */
class CoDocumentsHealthImport implements ToCollection, WithMultipleSheets
{
    use Importable;

    protected $data;
    protected $processedInvoices = [];
    protected $errors = [];
    
    // Mapeo de columnas del Excel DDB_DOCUMENTO_CAMPOS_DQ
    protected const COLUMN_MAP = [
        'CODPRESTADOR' => 0,
        'TIPODOCUMENTOIDENTIFICACION' => 1,
        'NUMDOCUMENTOIDENTIFICACION' => 2,
        'PRIMER_APELLIDO' => 3,
        'SEGUNDO_APELLIDO' => 4,
        'PRIMER_NOMBRE' => 5,
        'SEGUNDO_NOMBRE' => 6,
        'TIPO_USUARIO' => 7,
        'MODALIDAD_CONTRATACION' => 9,
        'COBERTURA_PLAN_BENEFICIOS' => 11,
        'NUMAUTORIZACION' => 13,
        'NUMERO_CONTRATO' => 16,
        'NUMERO_POLIZA' => 17,
        'COPAGO' => 18,
        'CUOTA_MODERADORA' => 19,
        'CUOTA_RECUPERACION' => 20,
        'PAGOS_COMPARTIDPS' => 21,
        'INVOICECODE' => 30,
        'ISSUEDATE' => 37,
        'ISSUETIME' => 38,
        'INVOICETYPECODE' => 39,
        'LINEEXTENSIONAMOUNT' => 42,
        'ALLOWANCETOTALAMOUNT' => 43,
        'TAXEXCLUSIVEAMOUNT' => 44,
        'TAXINCLUSIVEAMOUNT' => 45,
        'CHARGETOTALAMOUNT' => 46,
        'PAYABLEAMOUNT' => 48,
        // Datos del proveedor de servicios
        'ASP_PARTYNAME' => 50,
        'ASP_CITYCODE' => 51,
        'ASP_ADDRESSLINE' => 55,
        'ASP_COMPANYNIT' => 57,
        'ASP_CONTACT_TELEPHONE' => 61,
        'ASP_CONTACT_ELECTRONICMAIL' => 62,
        // Datos del cliente/EPS
        'ACP_PARTYNAME' => 65,
        'ACP_CITYCODE' => 66,
        'ACP_ADDRESSLINE' => 70,
        'ACP_COMPANYID' => 72,
        'ACP_CONTACT_TELEPHONE' => 76,
        // Datos de pago
        'PAYMENTMEANSCODE' => 99,
        'PAYMENTDUEDATE' => 101,
        // Línea de producto
        'INVOICELINEID' => 110,
        'UNITCODE' => 111,
        'INVOICEDQUANTITY' => 112,
        'PRODUCTVALUE' => 113,
        'ITEMDESCRIPTION' => 120,
        'SELLERSITEMIDENTIFICATION' => 121,
        // Campos específicos del sector salud
        'EPS' => 124,
        'FECHANACIMIENTO' => 125,
        'CODSEXO' => 126,
        'CODDIAGNOSTICOPRINCIPAL' => 130,
        'CODTECNOLOGIASALUD' => 134,
        'NOMTECNOLOGIASALUD' => 135,
        'CONCENTRACIONMEDICAMENTO' => 136,
        'FORMAFARMACEUTICA' => 138,
        'CANTIDADMEDICAMENTO' => 140,
        'DIASTRATAMIENTO' => 141,
        'FECHA_REAL_ENTREGA' => 142,
        'INVOICEPERIODSTARTDATE_1' => 145,
        'INVOICEPERIODENDDATE_1' => 146,
        'PREFIJO' => 147,
        'No_Resolucion' => 148
    ];

    public function sheets(): array
    {
        return [0 => $this];
    }

    private function throwException($message, $row = null)
    {
        $prefix = $row ? "Fila {$row}: " : "";
        $this->errors[] = $prefix . $message;
        throw new Exception($prefix . $message);
    }

    private function addError($message, $row = null)
    {
        $prefix = $row ? "Fila {$row}: " : "";
        $this->errors[] = $prefix . $message;
    }

    private function getColumnValue($row, $columnName)
    {
        $index = self::COLUMN_MAP[$columnName] ?? null;
        if ($index === null) return null;
        return $row[$index] ?? null;
    }

    private function ExcelDateToPHP($value)
    {
        try {
            if ($value === null || $value === '') return null;
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float)$value))->format('Y-m-d');
            }
            // Manejar formato DD/MM/YY
            if (is_string($value) && preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $value)) {
                $parts = explode('/', $value);
                $year = 2000 + intval($parts[2]);
                return Carbon::createFromFormat('d/m/Y', $parts[0] . '/' . $parts[1] . '/' . $year)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new Exception("Error al convertir fecha: {$value} - " . $e->getMessage());
        }
    }

    private function ExcelTimeToPHP($value)
    {
        try {
            if ($value === null || $value === '') return '00:00:00';
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float)$value))->format('H:i:s');
            }
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    private function toDecimal($value): float
    {
        if ($value === null || $value === '') return 0.0;
        if (is_numeric($value)) return (float)$value;
        $v = str_replace([' ', '\u00A0', ','], ['', '', '.'], (string)$value);
        return (float)$v;
    }

    private function validateHealthRow($row, $rowNumber)
    {
        // Validaciones específicas para sector salud
        $invoiceCode = $this->getColumnValue($row, 'INVOICECODE');
        $patientId = $this->getColumnValue($row, 'NUMDOCUMENTOIDENTIFICACION');
        $itemCode = $this->getColumnValue($row, 'SELLERSITEMIDENTIFICATION');
        $eps = $this->getColumnValue($row, 'EPS');

        if (empty($invoiceCode)) {
            $this->addError('Código de factura es obligatorio', $rowNumber);
        }

        if (empty($patientId)) {
            $this->addError('Identificación del paciente es obligatoria', $rowNumber);
        }

        if (empty($itemCode)) {
            $this->addError('Código del producto/servicio es obligatorio', $rowNumber);
        }

        if (empty($eps)) {
            $this->addError('EPS es obligatoria para facturación del sector salud', $rowNumber);
        }

        // Validar diagnóstico principal
        $diagnostico = $this->getColumnValue($row, 'CODDIAGNOSTICOPRINCIPAL');
        if (empty($diagnostico) || $diagnostico === '0') {
            $this->addError('Código de diagnóstico principal es obligatorio', $rowNumber);
        }

        // Validar tecnología en salud
        $codTecnologia = $this->getColumnValue($row, 'CODTECNOLOGIASALUD');
        if (empty($codTecnologia)) {
            $this->addError('Código de tecnología en salud es obligatorio', $rowNumber);
        }
    }

    private function buildHealthUserInfo($row)
    {
        $birthDate = $this->getColumnValue($row, 'FECHANACIMIENTO');
        $formattedBirthDate = null;
        
        if ($birthDate && is_string($birthDate) && preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $birthDate)) {
            $parts = explode('/', $birthDate);
            $year = 2000 + intval($parts[2]);
            $formattedBirthDate = Carbon::createFromFormat('d/m/Y', $parts[0] . '/' . $parts[1] . '/' . $year)->format('Y-m-d');
        }

        return [
            'provider_code' => $this->getColumnValue($row, 'CODPRESTADOR'),
            'health_type_document_identification_id' => $this->getColumnValue($row, 'TIPODOCUMENTOIDENTIFICACION'),
            'identification_number' => $this->getColumnValue($row, 'NUMDOCUMENTOIDENTIFICACION'),
            'surname' => $this->getColumnValue($row, 'PRIMER_APELLIDO'),
            'second_surname' => $this->getColumnValue($row, 'SEGUNDO_APELLIDO'),
            'first_name' => $this->getColumnValue($row, 'PRIMER_NOMBRE'),
            'middle_name' => $this->getColumnValue($row, 'SEGUNDO_NOMBRE'),
            'health_type_user_id' => $this->getColumnValue($row, 'TIPO_USUARIO_ID') ?? 4,
            'health_contracting_payment_method_id' => $this->getColumnValue($row, 'MODALIDAD_CONTRATACION_ID') ?? 12,
            'health_coverage_id' => $this->getColumnValue($row, 'COBERTURA_PLAN_BENEFICIOS_ID') ?? 1,
            'autorization_numbers' => $this->getColumnValue($row, 'NUMAUTORIZACION'),
            'mipres' => $this->getColumnValue($row, 'IDMIPRES'),
            'mipres_delivery' => $this->getColumnValue($row, 'NUMERO_ENTREGA_MIPRES'),
            'contract_number' => $this->getColumnValue($row, 'NUMERO_CONTRATO'),
            'policy_number' => $this->getColumnValue($row, 'NUMERO_POLIZA'),
            'co_payment' => $this->getColumnValue($row, 'COPAGO') ?? 0,
            'moderating_fee' => $this->getColumnValue($row, 'CUOTA_MODERADORA') ?? 0,
            'recovery_fee' => $this->getColumnValue($row, 'CUOTA_RECUPERACION') ?? 0,
            'shared_payment' => $this->getColumnValue($row, 'PAGOS_COMPARTIDPS') ?? 0,
            // Campos adicionales del sector salud
            'birth_date' => $formattedBirthDate,
            'gender_code' => $this->getColumnValue($row, 'CODSEXO'),
            'principal_diagnosis_code' => $this->getColumnValue($row, 'CODDIAGNOSTICOPRINCIPAL'),
            'health_technology_code' => $this->getColumnValue($row, 'CODTECNOLOGIASALUD'),
            'health_technology_name' => $this->getColumnValue($row, 'NOMTECNOLOGIASALUD'),
            'medication_concentration' => $this->getColumnValue($row, 'CONCENTRACIONMEDICAMENTO'),
            'pharmaceutical_form' => $this->getColumnValue($row, 'FORMAFARMACEUTICA'),
            'medication_quantity' => $this->getColumnValue($row, 'CANTIDADMEDICAMENTO'),
            'treatment_days' => $this->getColumnValue($row, 'DIASTRATAMIENTO'),
            'actual_delivery_date' => $this->ExcelDateToPHP($this->getColumnValue($row, 'FECHA_REAL_ENTREGA')),
        ];
    }

    private function calculateHealthRetentions($totalAmount, $eps)
    {
        // Cálculo de retenciones específicas del sector salud
        $retentions = [];
        
        // Retención en la fuente (4% para servicios de salud)
        $retefuente = $totalAmount * 0.04;
        if ($retefuente > 0) {
            $retentions[] = [
                'tax_id' => 6, // ID para retención en la fuente
                'tax_amount' => round($retefuente, 2),
                'percent' => 4.00,
                'taxable_amount' => $totalAmount,
            ];
        }

        // ReteICA (depende del municipio, ejemplo 0.414% para Bogotá)
        $reteica = $totalAmount * 0.00414;
        if ($reteica > 0) {
            $retentions[] = [
                'tax_id' => 7, // ID para ReteICA
                'tax_amount' => round($reteica, 2),
                'percent' => 0.414,
                'taxable_amount' => $totalAmount,
            ];
        }

        return $retentions;
    }

    public function collection(Collection $rows)
    {
        $filteredRows = $rows->filter(function($value, $key) {
            if ($key === 0) return false; // skip header
            // Verificar que tenga datos mínimos requeridos
            $invoiceCode = $value[self::COLUMN_MAP['INVOICECODE']] ?? null;
            $patientId = $value[self::COLUMN_MAP['NUMDOCUMENTOIDENTIFICACION']] ?? null;
            return !empty($invoiceCode) && !empty($patientId);
        });

        // Primera pasada: validación
        $rowNumber = 1;
        foreach ($filteredRows as $row) {
            $this->validateHealthRow($row, $rowNumber);
            $rowNumber++;
        }

        if (!empty($this->errors)) {
            throw new Exception("Errores de validación encontrados:\n" . implode("\n", $this->errors));
        }

        // Segunda pasada: agrupar por factura
        $groupedInvoices = [];
        foreach ($filteredRows as $row) {
            $invoiceCode = $this->getColumnValue($row, 'INVOICECODE');
            $patientId = $this->getColumnValue($row, 'NUMDOCUMENTOIDENTIFICACION');
            $key = $invoiceCode . '_' . $patientId;
            
            if (!isset($groupedInvoices[$key])) {
                $groupedInvoices[$key] = [
                    'header' => $row,
                    'lines' => []
                ];
            }
            
            $groupedInvoices[$key]['lines'][] = $row;
        }

        // Tercera pasada: procesar facturas agrupadas
        $send = new DocumentController();
        $docRequest = new \Modules\Factcolombia1\Http\Requests\Tenant\DocumentRequest();
        $total = count($groupedInvoices);
        $registered = 0;

        foreach ($groupedInvoices as $invoiceGroup) {
            try {
                $headerRow = $invoiceGroup['header'];
                $lines = $invoiceGroup['lines'];
                
                $json = $this->buildHealthInvoiceJson($headerRow, $lines);
                
                \Log::info("Procesando factura sector salud: " . json_encode($json, JSON_PRETTY_PRINT));
                
                $result = $send->store($docRequest, json_encode($json));
                $resultArr = $this->unpackControllerResponse($result);
                
                if (!empty($resultArr) && ($resultArr['success'] ?? false) && isset($resultArr['data']['id'])) {
                    $document = Document::find($resultArr['data']['id']);
                    if ($document) {
                        $this->processedInvoices[] = [
                            'invoice_code' => $this->getColumnValue($headerRow, 'INVOICECODE'),
                            'patient_id' => $this->getColumnValue($headerRow, 'NUMDOCUMENTOIDENTIFICACION'),
                            'document_id' => $document->id,
                            'total_amount' => $document->total,
                            'lines_count' => count($lines)
                        ];
                        
                        // Enviar correo si está aprobada
                        if ($document->state_document_id == 5 && auth()->check()) {
                            $this->sendHealthInvoiceEmail($document);
                        }
                    }
                    $registered++;
                } else {
                    $this->addError("Error al procesar factura: " . ($resultArr['message'] ?? 'Error desconocido'));
                }
                
            } catch (\Exception $e) {
                $this->addError("Error procesando factura: " . $e->getMessage());
                \Log::error("Error en factura sector salud: " . $e->getMessage());
            }
        }

        $this->data = compact('total', 'registered');
        $this->data['processed_invoices'] = $this->processedInvoices;
        $this->data['errors'] = $this->errors;
    }

    private function buildHealthInvoiceJson($headerRow, $lines)
    {
        $invoiceCode = $this->getColumnValue($headerRow, 'INVOICECODE');
        $issueDate = $this->ExcelDateToPHP($this->getColumnValue($headerRow, 'ISSUEDATE'));
        $issueTime = $this->ExcelTimeToPHP($this->getColumnValue($headerRow, 'ISSUETIME'));
        
        // Buscar o crear el paciente
        $patientId = $this->getColumnValue($headerRow, 'NUMDOCUMENTOIDENTIFICACION');
        $person = Person::where('number', $patientId)->first();
        
        if (!$person) {
            // Crear el paciente automáticamente
            $person = Person::create([
                'type_person_id' => 2, // Persona natural
                'identity_document_type_id' => 3, // CC
                'number' => $patientId,
                'name' => trim($this->getColumnValue($headerRow, 'PRIMER_NOMBRE') . ' ' . 
                              $this->getColumnValue($headerRow, 'SEGUNDO_NOMBRE') . ' ' . 
                              $this->getColumnValue($headerRow, 'PRIMER_APELLIDO') . ' ' . 
                              $this->getColumnValue($headerRow, 'SEGUNDO_APELLIDO')),
                'code' => $patientId,
                'type_regime_id' => 2, // Simplificado
                'type_obligation_id' => 1, // No responsable de IVA
                'municipality_id_fact' => 12688, // Por defecto
                'email' => 'noemail@example.com',
                'telephone' => '000000000',
                'address' => 'No registrada'
            ]);
        }

        // Buscar EPS
        $epsName = $this->getColumnValue($headerRow, 'ACP_PARTYNAME');
        $epsNit = $this->getColumnValue($headerRow, 'ACP_COMPANYID');
        $epsCustomer = Person::where('number', $epsNit)->first();
        
        if (!$epsCustomer) {
            $epsCustomer = Person::create([
                'type_person_id' => 1, // Jurídica
                'identity_document_type_id' => 6, // NIT
                'number' => $epsNit,
                'name' => $epsName,
                'code' => $epsNit,
                'type_regime_id' => 1, // Común
                'type_obligation_id' => 2, // Responsable de IVA
                'municipality_id_fact' => $this->getColumnValue($headerRow, 'ACP_CITYCODE') ?? 12688,
                'email' => 'facturacion@eps.com.co',
                'telephone' => $this->getColumnValue($headerRow, 'ACP_CONTACT_TELEPHONE') ?? '000000000',
                'address' => $this->getColumnValue($headerRow, 'ACP_ADDRESSLINE') ?? 'No registrada'
            ]);
        }

        $totalAmount = $this->toDecimal($this->getColumnValue($headerRow, 'PAYABLEAMOUNT'));
        $subtotalAmount = $this->toDecimal($this->getColumnValue($headerRow, 'TAXEXCLUSIVEAMOUNT'));
        
        $json = [
            'number' => $invoiceCode,
            'type_document_id' => 1,
            'date' => $issueDate,
            'time' => $issueTime,
            'resolution_number' => $this->getColumnValue($headerRow, 'No_Resolucion'),
            'prefix' => $this->getColumnValue($headerRow, 'PREFIJO'),
            'notes' => 'Factura sector salud - EPS: ' . $epsName,
            'customer' => [
                'customer_id' => $epsCustomer->id,
                'identification_number' => $epsCustomer->code,
                'dv' => $epsCustomer->dv,
                'name' => $epsCustomer->name,
                'city_id' => $epsCustomer->municipality_id_fact ?? 12688,
                'phone' => $epsCustomer->telephone,
                'address' => $epsCustomer->address,
                'email' => $epsCustomer->email,
                'type_organization_id' => $epsCustomer->type_person_id,
                'type_document_identification_id' => $epsCustomer->identity_document_type_id,
                'type_liability_id' => $epsCustomer->type_obligation_id,
                'type_regime_id' => $epsCustomer->type_regime_id,
                'merchant_registration' => "00000000",
            ],
            'payment_form' => [
                'payment_form_id' => PaymentForm::where('name', 'like', '%credito%')->first()->id ?? 2,
                'payment_method_id' => PaymentMethod::where('name', 'like', '%transferencia%')->first()->id ?? 31,
                'payment_due_date' => $this->ExcelDateToPHP($this->getColumnValue($headerRow, 'PAYMENTDUEDATE')) ?? $issueDate,
                'duration_measure' => 30,
            ],
            'legal_monetary_totals' => [
                'line_extension_amount' => number_format($subtotalAmount, 2, '.', ''),
                'tax_exclusive_amount' => number_format($subtotalAmount, 2, '.', ''),
                'tax_inclusive_amount' => number_format($totalAmount, 2, '.', ''),
                'allowance_total_amount' => number_format($this->toDecimal($this->getColumnValue($headerRow, 'ALLOWANCETOTALAMOUNT')), 2, '.', ''),
                'charge_total_amount' => number_format($this->toDecimal($this->getColumnValue($headerRow, 'CHARGETOTALAMOUNT')), 2, '.', ''),
                'payable_amount' => number_format($totalAmount, 2, '.', '')
            ],
            'total' => $totalAmount,
            'health_fields' => [
                'invoice_period_start_date' => $this->ExcelDateToPHP($this->getColumnValue($headerRow, 'INVOICEPERIODSTARTDATE_1')),
                'invoice_period_end_date' => $this->ExcelDateToPHP($this->getColumnValue($headerRow, 'INVOICEPERIODENDDATE_1')),
            ],
            'users_info' => [$this->buildHealthUserInfo($headerRow)],
            'invoice_lines' => []
        ];

        // Agregar retenciones del sector salud
        $retentions = $this->calculateHealthRetentions($totalAmount, $epsName);
        if (!empty($retentions)) {
            $json['with_holding_tax_total'] = $retentions;
        }

        // Procesar líneas de productos/servicios
        foreach ($lines as $lineRow) {
            $itemCode = $this->getColumnValue($lineRow, 'SELLERSITEMIDENTIFICATION');
            $itemDescription = $this->getColumnValue($lineRow, 'ITEMDESCRIPTION');
            $quantity = $this->toDecimal($this->getColumnValue($lineRow, 'INVOICEDQUANTITY'));
            $unitPrice = $this->toDecimal($this->getColumnValue($lineRow, 'PRODUCTVALUE'));
            
            // Buscar o crear el item
            $item = Item::where('internal_id', $itemCode)->first();
            if (!$item) {
                $item = Item::create([
                    'internal_id' => $itemCode,
                    'name' => $itemDescription,
                    'description' => $itemDescription,
                    'unit_type_id' => 1, // Unidad por defecto
                    'tax_id' => 1, // Sin impuestos para medicamentos
                    'sale_price' => $unitPrice
                ]);
            }

            $lineTotal = $quantity * $unitPrice;
            
            $json['invoice_lines'][] = [
                'item_id' => $item->id,
                'unit_type_id' => $item->unit_type_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_id' => 1, // Sin impuestos para medicamentos del sector salud
                'total_tax' => 0,
                'subtotal' => $lineTotal,
                'discount' => 0,
                'total' => $lineTotal,
                'unit_measure_id' => $this->getColumnValue($lineRow, 'UNITCODE') ?? 'EA',
                'invoiced_quantity' => $quantity,
                'line_extension_amount' => $lineTotal,
                'free_of_charge_indicator' => false,
                'description' => $itemDescription,
                'code' => $itemCode,
                'type_item_identification_id' => 4,
                'price_amount' => $unitPrice,
                'base_quantity' => 1,
            ];
        }

        return $json;
    }

    private function sendHealthInvoiceEmail($document)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $establishment = $user->establishment_id ? 
                \App\Models\Tenant\Establishment::find($user->establishment_id) : null;
                
            if ($establishment && $establishment->email) {
                $send = new DocumentController();
                $send->sendEmailCoDocument(new \Illuminate\Http\Request([
                    'number' => $document->number,
                    'email' => $establishment->email,
                    'number_full' => $document->prefix . '-' . $document->number
                ]));
            }
        }
    }

    private function unpackControllerResponse($result): array
    {
        if (is_array($result)) return $result;
        if ($result instanceof \Illuminate\Http\Response) {
            try {
                $content = $result->getContent();
                $decoded = json_decode($content, true);
                if (is_array($decoded)) return $decoded;
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return [];
    }

    public function getData()
    {
        return $this->data;
    }

    public function getProcessedInvoices()
    {
        return $this->processedInvoices;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
