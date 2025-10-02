<?php

namespace App\Imports;

use App\Models\Tenant\Document;
use App\Models\Tenant\Person;
use App\Models\Tenant\Item;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use Exception;
use Modules\Factcolombia1\Models\Tenant\PaymentForm;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Factcolombia1\Http\Requests\Tenant\DocumentRequest;
use Modules\Factcolombia1\Models\TenantService\Municipality;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CoDocumentsImport implements ToCollection, WithMultipleSheets
{
    use Importable;

    protected $data;
    protected const DATE_BASE = '1900-01-01';
    protected const SECONDS_PER_DAY = 86400;
    protected const MAX_DAYS_DIFF = 10;

    public function sheets(): array
    {
        return [0 => $this];
    }

    private function throwException($message, $row = null)
    {
        $prefix = $row ? "Registro nro. {$row}: " : "";
        throw new Exception($prefix . $message);
    }

    private function ExcelDateToPHP($value)
    {
        try {
            if (is_numeric($value)) {
                $baseDate = new \DateTime(self::DATE_BASE);
                return $baseDate->modify('+' . ($value - 2) . ' days')->format('Y-m-d');
            } else {
                return Carbon::parse($value)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            throw new Exception("Error al convertir fecha Excel: " . $e->getMessage());
        }
    }

    private function ExcelTimeToPHP($value)
    {
        try {
            if (is_numeric($value)) {
                $secondsFrom19070 = $value * self::SECONDS_PER_DAY;
                return (new \DateTime('@' . $secondsFrom19070))->format('H:i:s');
            } else {
                return Carbon::parse($value)->format('H:i:s');
            }
        } catch (\Exception $e) {
            throw new Exception("Error al convertir hora Excel: " . $e->getMessage());
        }
    }

    private function validateRow($row, $rowNumber)
    {
        if (empty($row[10])) {
            $this->throwException('El campo identificación cliente es obligatorio', $rowNumber);
        }

        $this->validateDocument($row, $rowNumber);
        $this->validatePerson($row, $rowNumber);
        $this->validateItem($row, $rowNumber);
        $this->validateMunicipality($row, $rowNumber);
        $this->validateDate($row, $rowNumber);
    }

    private function validateDocument($row, $rowNumber)
    {
        if (Document::where('prefix', $row[4])->where('number', $row[0])->exists()) {
            $this->throwException("El documento {$row[4]}-{$row[0]} ya fue registrado", $rowNumber);
        }
    }

    private function validatePerson($row, $rowNumber)
    {
        $personNumber = strval(trim($row[10]));
        
        // Remover puntos, comas y espacios del número de identificación
        $cleanPersonNumber = preg_replace('/[^0-9]/', '', $personNumber);
        
        // Buscar por el número original y también por el número limpio
        $personExists = Person::where('number', $personNumber)
                             ->orWhere('number', $cleanPersonNumber)
                             ->exists();
                             
        if (!$personExists) {
            $this->throwException("No existe el documento {$personNumber} en la base de datos", $rowNumber);
        }
    }

    private function validateItem($row, $rowNumber)
    {
        if (!empty($row[23]) &&
            !Item::where('internal_id', $row[23])
                  ->orWhere('name', $row[23])
                  ->exists()) {
            $this->throwException("No existe el item {$row[23]} en la base de datos", $rowNumber);
        }
    }

    private function validateMunicipality($row, $rowNumber)
    {
        if (!empty($row[8]) && !Municipality::where('id', $row[8])->exists()) {
            $this->throwException("Código de municipio inválido: {$row[8]}", $rowNumber);
        }
    }

    private function validateDate($row, $rowNumber)
    {
        if (!empty($row[1])) {
            $actualDate = Carbon::now();
            $documentDate = Carbon::parse($this->ExcelDateToPHP($row[1]));

            if ($actualDate->diffInDays($documentDate) >= self::MAX_DAYS_DIFF) {
                $this->throwException('La fecha no puede ser mayor o igual a 10 días antes de la fecha actual', $rowNumber);
            }
        }
    }

    private function processUserInfo($userInfoString)
    {
        return array_map(function ($user) {
            $userData = explode(',', $user);
            return [
                'provider_code' => $userData[0] ?? '',
                'health_type_document_identification_id' => $userData[1] ?? '',
                'identification_number' => $userData[2] ?? '',
                'surname' => $userData[3] ?? '',
                'second_surname' => $userData[4] ?? '',
                'first_name' => $userData[5] ?? '',
                'middle_name' => $userData[6] ?? '',
                'health_type_user_id' => $userData[7] ?? '',
                'health_contracting_payment_method_id' => $userData[8] ?? '',
                'health_coverage_id' => $userData[9] ?? '',
                'autorization_numbers' => $userData[10] ?? '',
                'mipres' => $userData[11] ?? '',
                'mipres_delivery' => $userData[12] ?? '',
                'contract_number' => $userData[13] ?? '',
                'policy_number' => $userData[14] ?? '',
                'co_payment' => $userData[15] ?? '',
                'moderating_fee' => $userData[16] ?? '',
                'recovery_fee' => $userData[17] ?? '',
                'shared_payment' => $userData[18] ?? '',
            ];
        }, explode('%', $userInfoString));
    }

    public function collection(Collection $rows)
    {
        $filteredRows = $rows->filter(fn($value, $key) => $key > 0 && !empty(array_filter($value->toArray())));

        // Primera pasada: validación
        $rowNumber = 1;
        foreach ($filteredRows as $row) {
            $this->validateRow($row, $rowNumber);
            $rowNumber++;
        }

        $total = count($filteredRows);
        $registered = 0;
        $send = new DocumentController();
        $request = new DocumentRequest();
        $previos_prefix_number = "";
        $json = [];
        $invoice_lines = [];

        \Log::info("🚀 Iniciando importación de {$total} documentos");

        foreach ($filteredRows as $row) {
            $current_prefix_number = $row[4] . $row[0];

            // Si cambió el documento, procesar el anterior
            if ($current_prefix_number != $previos_prefix_number && $previos_prefix_number != "") {
                $json['invoice_lines'] = $invoice_lines;
                
                \Log::info("💾 Guardando documento: {$previos_prefix_number}");
                $result = $send->store($request, json_encode($json));

                if ($result['success'] && isset($result['data']['id'])) {
                    \Log::info("✅ Documento {$previos_prefix_number} guardado exitosamente con ID: {$result['data']['id']}");
                    
                    // Verificar si la factura fue aprobada y enviar el correo
                    $document = Document::find($result['data']['id']);
                    if ($document && $document->state_document_id == 5) {
                        $establishment = \App\Models\Tenant\Establishment::find(auth()->user()->establishment_id);
                        $send->sendEmailCoDocument(new \Illuminate\Http\Request([
                            'number' => $document->number,
                            'email' => $establishment->email ?? null,
                            'number_full' => $document->prefix . '-' . $document->number
                        ]));
                        \Log::info("📧 Email enviado para documento aprobado: {$previos_prefix_number}");
                    }
                } else {
                    \Log::error("❌ Error guardando documento {$previos_prefix_number}: " . json_encode($result));
                }

                $invoice_lines = []; // Reiniciar para el nuevo documento
                sleep(3); // Reducir pausa entre documentos
                $registered++;
                
                \Log::info("📊 Progreso: {$registered}/{$total} documentos procesados");
            }

            // Si es un nuevo documento, crear la estructura base
            if ($current_prefix_number != $previos_prefix_number) {
                \Log::info("🆕 Iniciando nuevo documento: {$current_prefix_number}");
                
                $date = $this->ExcelDateToPHP($row[1]);
                $time = $this->ExcelTimeToPHP($row[2]);
                
                // Buscar persona de manera más robusta
                $personNumber = strval(trim($row[10]));
                $cleanPersonNumber = preg_replace('/[^0-9]/', '', $personNumber);
                
                $person = Person::where('number', $personNumber)
                                ->orWhere('number', $cleanPersonNumber)
                                ->firstOrFail();

                $json = [
                    'number' => $row[0],
                    'type_document_id' => 1,
                    'date' => $date,
                    'time' => $time,
                    'resolution_number' => $row[3],
                    'prefix' => $row[4],
                    'notes' => $row[27] ?? '',
                    'establishment_name' => $row[5],
                    'establishment_address' => $row[6],
                    'establishment_phone' => $row[7],
                    'establishment_municipality' => $row[8],
                    'establishment_email' => $row[9],
                    'customer' => [
                        'customer_id' => $person->id,
                        'identification_number' => $person->code,
                        'dv' => $person->dv,
                        'name' => $person->name,
                        'city_id' => $person->municipality_id_fact ?? 12688,
                        'phone' => $person->telephone,
                        'address' => $person->address,
                        'email' => $person->email,
                        'type_organization_id' => $person->type_person_id,
                        'type_document_identification_id' => $person->identity_document_type_id,
                        'type_liability_id' => $person->type_obligation_id,
                        'type_regime_id' => $person->type_regime_id,
                        'merchant_registration' => "00000000",
                    ],
                    'payment_form' => [
                        'payment_form_id' => is_string($row[11]) ? PaymentForm::where('name', 'like', '%' . trim($row[11]) . '%')->firstOrFail()->id : $row[11],
                        'payment_method_id' => is_string($row[12]) ? PaymentMethod::where('name', 'like', '%' . trim($row[12]) . '%')->firstOrFail()->id : $row[12],
                        'payment_due_date' => $this->ExcelDateToPHP($row[13]),
                        'duration_measure' => $row[14],
                    ],
                    'legal_monetary_totals' => [
                        'line_extension_amount' => number_format((float)$row[15], 2, '.', ''),
                        'tax_exclusive_amount' => number_format((float)$row[16], 2, '.', ''),
                        'tax_inclusive_amount' => number_format((float)$row[17], 2, '.', ''),
                        'allowance_total_amount' => number_format((float)$row[18], 2, '.', ''),
                        'charge_total_amount' => number_format((float)($row[19] ?? 0), 2, '.', ''),
                        'payable_amount' => number_format((float)$row[20], 2, '.', '')
                    ],
                    'head_note' => $row[25] ?? '',
                    'foot_note' => $row[26] ?? '',
                ];

                // Campos de impuestos totales si están presentes
                if (!empty($row[31]) && !empty($row[32])) {
                    $json['tax_totals'] = [
                        [
                            'tax_id' => (int)$row[31],
                            'tax_amount' => (float)$row[32],
                            'percent' => (float)($row[33] ?? 0),
                            'taxable_amount' => (float)($row[34] ?? 0)
                        ]
                    ];
                }

                // Campos de salud si están presentes
                if (!empty($row[28]) && !empty($row[29]) && !empty($row[30])) {
                    $json['health_fields'] = [
                        'invoice_period_start_date' => $this->ExcelDateToPHP($row[28]),
                        'invoice_period_end_date' => $this->ExcelDateToPHP($row[29]),
                    ];
                    $json['users_info'] = $this->processUserInfo($row[30]);
                }

                $previos_prefix_number = $current_prefix_number;
            }

            // Agregar línea del documento
            $item = Item::where('internal_id', $row[23])->orWhere('name', $row[23])->firstOrFail();

            $invoice_line = [
                'item_id' => $item->id,
                'unit_type_id' => $item->unit_type_id,
                'quantity' => $row[21],
                'unit_price' => $row[24],
                'tax_id' => !empty($row[35]) ? $row[35] : $item->tax_id,
                'total_tax' => !empty($row[36]) ? (float)$row[36] : 0,
                'subtotal' => $row[22],
                'discount' => $row[41] ?? 0,
                'total' => ($row[24] * $row[21]) - ($row[41] ?? 0),
                'unit_measure_id' => $item->unit_type->code,
                'invoiced_quantity' => $row[21],
                'line_extension_amount' => $row[22],
                'free_of_charge_indicator' => ($row[39] ?? 'false') === 'true',
                'description' => $item->description ?? $item->name ?? '',
                'code' => strval($row[23]),
                'type_item_identification_id' => 4,
                'price_amount' => $row[24],
                'base_quantity' => 1,
            ];

            // Impuestos de línea si están presentes
            if (!empty($row[35]) && !empty($row[36])) {
                $invoice_line['tax_totals'] = [[
                    'tax_id' => (int)$row[35],
                    'tax_amount' => (float)$row[36],
                    'percent' => (float)($row[37] ?? 0),
                    'taxable_amount' => (float)($row[38] ?? 0),
                ]];
            }

            // Descuentos/cargos de línea si están presentes
            if (!empty($row[39]) || !empty($row[40]) || !empty($row[41])) {
                $invoice_line['allowance_charges'] = [[
                    'charge_indicator' => ($row[39] ?? 'false') === 'true',
                    'allowance_charge_reason' => $row[40] ?? null,
                    'amount' => $row[41] ?? 0,
                    'base_amount' => $row[42] ?? 0,
                ]];
            }

            $invoice_lines[] = $invoice_line;
        }

        // Procesar último documento
        if (!empty($json)) {
            $json['invoice_lines'] = $invoice_lines;
            \Log::info("💾 Guardando último documento: {$previos_prefix_number}");
            $result = $send->store($request, json_encode($json));
            
            if ($result['success']) {
                \Log::info("✅ Último documento guardado exitosamente");
            } else {
                \Log::error("❌ Error guardando último documento: " . json_encode($result));
            }
            
            $registered++;
        }

        \Log::info("🎉 Importación completada: {$registered}/{$total} documentos procesados");
        $this->data = compact('total', 'registered');
    }

    public function getData()
    {
        return $this->data;
    }
}
