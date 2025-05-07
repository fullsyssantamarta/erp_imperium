<?php

namespace App\Imports;

use App\Models\Tenant\Document;
use App\Models\Tenant\Person;
use App\Models\Tenant\Item;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use DateTime;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Factcolombia1\Http\Requests\Tenant\DocumentRequest;
use Modules\Factcolombia1\Models\Tenant\PaymentForm;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;
use Exception;
use Modules\Factcolombia1\Models\SystemService\Municipality;
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
            $baseDate = new \DateTime(self::DATE_BASE);
            return $baseDate->modify('+' . ($value - 2) . ' days')->format('Y-m-d');
        } catch (\Exception $e) {
            throw new Exception("Error al convertir fecha Excel: " . $e->getMessage());
        }
    }

    private function ExcelTimeToPHP($value)
    {
        try {
            $secondsFrom19070 = $value * self::SECONDS_PER_DAY;
            return (new \DateTime('@' . $secondsFrom19070))->format('H:i:s');
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
        if (!Person::where('number', $personNumber)->exists()) {
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
        return array_map(function($user) {
            $userData = explode(',', $user);
            return [
                'provider_code' => $userData[0],
                'health_type_document_identification_id' => $userData[1],
                'identification_number' => $userData[2],
                'surname' => $userData[3],
                'second_surname' => $userData[4],
                'first_name' => $userData[5],
                'middle_name' => $userData[6],
                'health_type_user_id' => $userData[7],
                'health_contracting_payment_method_id' => $userData[8],
                'health_coverage_id' => $userData[9],
                'autorization_numbers' => $userData[10],
                'mipres' => $userData[11],
                'mipres_delivery' => $userData[12],
                'contract_number' => $userData[13],
                'policy_number' => $userData[14],
                'co_payment' => $userData[15],
                'moderating_fee' => $userData[16],
                'recovery_fee' => $userData[17],
                'shared_payment' => $userData[18],
            ];
        }, explode('%', $userInfoString));
    }

    public function collection(Collection $rows)
    {
        $filteredRows = $rows->filter(fn($value, $key) => $key > 0 && !empty(array_filter($value->toArray())));
        
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

        foreach ($filteredRows as $row) {
            if ($row[4] . $row[0] != $previos_prefix_number) {
                if ($previos_prefix_number != "") {
                    $result = $send->store($request, json_encode($json));
                    
                    // Verificar si la factura fue aprobada y enviar el correo
                    if ($result['success'] && isset($result['data']['id'])) {
                        $document = Document::find($result['data']['id']);
                        if ($document && $document->state_document_id == 5) { // 5 = ACCEPTED
                            // Obtener el email de la empresa desde establishment
                            $establishment = \App\Models\Tenant\Establishment::where('id', auth()->user()->establishment_id)->first();
                            $company_email = $establishment->email;
                            
                            // Enviar correo al email de la empresa
                            $send->sendEmailCoDocument(new \Illuminate\Http\Request([
                                'number' => $document->number,
                                'email' => $company_email,
                                'number_full' => $document->prefix.'-'.$document->number
                            ]));
                        }
                    }
                    
                    sleep(5);
                }
                $previos_prefix_number = $row[4] . $row[0];
                $number = $row[0];
                $date = $this->ExcelDateToPHP($row[1]);
                $time = $this->ExcelTimeToPHP($row[2]);
                $resolution_number = $row[3];
                $prefix = $row[4];
                $notes = $row[27];
                $establishment_name = $row[5];
                $establishment_address = $row[6];
                $establishment_phone = $row[7];
                $establishment_municipality = $row[8];
                $establishment_email = $row[9];
                $head_note = $row[26];
                $foot_note = $row[27];
                $person = Person::where('number', $row[10])->firstOrFail();
                $customer = [
                    'customer_id' => $person->id,
                    'identification_number' => $person->code,
                    'dv' => $person->dv,
                    'name' => $person->name,
                    'municipality_id_fact' => $person->city_id,
                    'phone' => $person->telephone,
                    'address' => $person->address,
                    'email' => $person->email,
                    'type_organization_id' => $person->type_person_id,
                    'type_document_identification_id' => $person->identity_document_type_id,
                    'type_liability_id' => $person->type_obligation_id,
                    'type_regime_id' => $person->type_regime_id,
                    'merchant_registration' => "00000000",
                ];
                $payment_form = [
                    'payment_form_id' => is_string($row[11]) ? PaymentForm::where('name', 'like', '%' . str_replace('_x000D_', '', $row[11]) . '%')->firstOrFail()->id : $row[11],
                    'payment_method_id' => is_string($row[12]) ? PaymentMethod::where('name', 'like', '%' . str_replace('_x000D_', '', $row[12]) . '%')->firstOrFail()->id : $row[12],
                    'payment_due_date' => $this->ExcelDateToPHP($row[13]),
                    'duration_measure' => $row[14],
                ];
                $legal_monetary_totals = [
                    'line_extension_amount' => $row[15],
                    'tax_exclusive_amount' => $row[16],
                    'tax_inclusive_amount' => $row[17],
                    'allowance_total_amount' => $row[18],
                    'charge_total_amount' => $row[19],
                    'payable_amount' => $row[20],
                ];
                if ($row[28] != '' && $row[29] != '' && $row[30] != '') {
                    $health_fields = [
                        'invoice_period_start_date' => $this->ExcelDateToPHP($row[28]),
                        'invoice_period_end_date' => $this->ExcelDateToPHP($row[29]),
                    ];

                    $users_info = $this->processUserInfo($row[30]);
                }
                $invoice_lines = [];
            }
            $item = Item::where('internal_id', $row[23])
                        ->orWhere('name', $row[23])
                        ->firstOrFail();
                        
            $invoice_line = [
                'item_id' => $item->id,
                'unit_type_id' => $item->unit_type_id,
                'quantity' => $row[21],
                'unit_price' => $row[24],
                'tax_id' => $item->tax_id,
                'total_tax' => ($row[24] * $row[21]) - $row[22],
                'subtotal' => $row[22],
                'discount'  => 0,
                'total' => ($row[24] * $row[21]) - 0,
                'unit_measure_id' => $item->unit_type->code,
                'invoiced_quantity' => $row[21],
                'line_extension_amount' => $row[22],
                'free_of_charge_indicator' => false,
                'description' => $item->description ?? $item->name ?? '',
                'code' => strval($row[23]),
                'type_item_identification_id' => 4,
                'price_amount' => $row[24],
                'base_quantity' => 1,
            ];

            $invoice_lines[] = $invoice_line;

            $json = array(
                'number' => $number,
                'type_document_id' => 1,
                'date' => $date,
                'time' => $time,
                'resolution_number' => $resolution_number,
                'prefix' => $prefix,
                'notes' => $notes,
                'establishment_name' => $establishment_name,
                'establishment_address' => $establishment_address,
                'establishment_phone' => $establishment_phone,
                'establishment_municipality' => $establishment_municipality,
                'establishment_email' => $establishment_email,
                'customer' => $customer,
                'payment_form' => $payment_form,
                'legal_monetary_totals' => $legal_monetary_totals,
                'invoice_lines' => $invoice_lines,
                'head_note' => $head_note,
                'foot_note' => $foot_note,
            );
            $registered += 1;
            $this->data = compact('total', 'registered');
            if ($row[28] != '' && $row[29] != '' && $row[30] != '') {
                $json['health_fields'] = $health_fields;
                $json['users_info'] = $users_info;
            }
        }
        if ($row[28] != '' && $row[29] != '' && $row[30] != '') {
            $json['health_fields'] = $health_fields;
            $json['users_info'] = $users_info;
        }
        \Log::debug(json_encode($json));
        $result = $send->store($request, json_encode($json));

        $this->data = compact('total', 'registered');
        return;
    }

    public function getData()
    {
        return $this->data;
    }
}
