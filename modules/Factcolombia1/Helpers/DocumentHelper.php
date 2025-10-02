<?php

namespace Modules\Factcolombia1\Helpers;

use App\Models\Tenant\Document;
use App\Models\Tenant\Person;
use App\Models\Tenant\Item;
use App\CoreFacturalo\Requests\Inputs\Common\EstablishmentInput;
use Illuminate\Support\Str;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Factcolombia1\Models\Tenant\{
    Tax,
};
use Modules\Finance\Traits\FinanceTrait;
use Exception;
use App\Services\HealthFieldsValidatorService;


class DocumentHelper
{
    use FinanceTrait;

    protected $apply_change;

    public static function createDocument($request, $nextConsecutive, $correlative_api, $company, $response, $response_status, $type_environment_id)
    {
//\Log::debug("1");
        $establishment = EstablishmentInput::set(auth()->user()->establishment_id);
        $shipping_two_steps = ($type_environment_id == 2);
    $document = new Document();
    $document->prefix = $request->prefix ?? $nextConsecutive->prefix;
        $document->number = $correlative_api;
        $document->user_id = auth()->id();
        $document->seller_id = $request->seller_id ?? null;
        $document->external_id = Str::uuid()->toString();
        $document->establishment_id = auth()->user()->establishment_id;
        $document->establishment = $establishment;
        $document->soap_type_id = Company::active()->soap_type_id;
//        $document->calculationrate = $request->calculationrate ? $request->calculationrate : 0;
//        $document->calculationratedate = $request->calculationratedate ? $request->calculationratedate : Carbon::parse("1900-01-01")->format('Y-m-d');
//        $document->incoterm_id = $request->incoterm_id ? $request->incoterm_id : null;
//\Log::debug("2");
        $document->send_server = false;
        $document->type_environment_id = $type_environment_id;
        $document->shipping_two_steps = $shipping_two_steps;
        $document->type_document_id = $request->type_document_id;
        $document->type_invoice_id = $request->type_invoice_id;
        $document->customer_id = $request->customer_id;
        $document->customer = Person::with('typePerson', 'typeRegime', 'identity_document_type', 'country', 'department', 'city')->findOrFail($request->customer_id);
        $document->currency_id = $request->currency_id;
        $document->date_expiration = $request->date_expiration ? Carbon::parse("{$request->date_expiration}") : Carbon::parse($request->date_issue)->format('Y-m-d');
        $document->date_of_issue = Carbon::parse($request->date_issue)->format('Y-m-d');
        $document->time_of_issue = Carbon::now()->format('H:i:s');
        $document->observation = $request->observation;
        $document->reference_id = $request->reference_id;
        $document->note_concept_id = $request->note_concept_id;
        $document->sale = $request->sale;
        $document->total_discount = $request->total_discount;
//\Log::debug("3");
        $document->taxes = $request->taxes;
        $document->total_tax = $request->total_tax;
        $document->subtotal = $request->subtotal;
        $document->total = $request->total;
        $document->version_ubl_id = $company->version_ubl_id;
        $document->ambient_id = $company->ambient_id;
        $document->payment_form_id = $request->payment_form_id;
        $document->payment_method_id = $request->payment_method_id;
        $document->time_days_credit = $request->time_days_credit;
        $document->response_api = $response;
        $document->response_api_status = $response_status;
        $document->state_document_id = $request->state_document_id;
        $document->correlative_api = $correlative_api;
        $document->sale_note_id = $request->sale_note_id;
        $document->remission_id = $request->remission_id;
//\Log::debug("4");
        $document->xml = $request->xml;
        $document->cufe = $request->cufe;
//\Log::debug("4.1");
        $document->order_reference = self::getOrderReference($request);
//\Log::debug("4.2");
        $document->health_fields = self::getHealthfields($request);
//\Log::debug("4.3");
//\Log::debug($request);

//\Log::debug(json_encode($document));
        try{
            $document->save();
        } catch (\Exception $e) {
            \Log::debug($e->getMessage());
        }
//\Log::debug("4.4");
        $existen_items = $document->items;
        $existen_items->each->delete();
//\Log::debug("5");
//\Log::debug($request->items);
        foreach ($request->items as $item) {
            $exist_record = false;
            $record_item = Item::find((key_exists('item_id', $item)) ? $item['item_id'] : 0);
//\Log::debug($record_item);
            if($record_item === null){
//                \Log::debug(1);
                $exist_record = false;
                $record_item = Item::where('internal_id', $item['code'])->get();
                if(count($record_item) == 0){
//                    \Log::debug(11);
                    $exist_record = false;
                }
                else{
//                    \Log::debug(12);
                    $exist_record = true;
                    $record_item = Item::where('internal_id', $item['code'])->firstOrFail();
                }
            }
            else{
//                \Log::debug(2);
                $exist_record = true;
            }
//            \Log::debug(json_encode($exist_record));
//            \Log::debug($item);
            if(!$exist_record){
                $record_item = new Item();
                $record_item->name = (key_exists('description', $item)) ? $item['description'] : $item['item']['name'];
//                \Log::debug("A");
                $record_item->second_name = (key_exists('description', $item)) ? $item['description'] : $item['item']['name'];
                $record_item->description = (key_exists('description', $item)) ? $item['description'] : $item['item']['name'];
                $record_item->item_type_id = "01";
                $record_item->internal_id = $item['code'];
                $record_item->tax_id = null;
                $record_item->purchase_tax_id = null;
                $record_item->unit_type_id = 10;
                $record_item->currency_type_id = 170;
                $record_item->sale_unit_price = (key_exists('price_amount', $item)) ? $item['price_amount'] : $item['item']['sale_unit_price'];
//                \Log::debug("B");
                $record_item->amount_plastic_bag_taxes = 0.1;
                $record_item->is_set = 0;
                $record_item->model = $item['code'];
                $record_item->image = "imagen-no-disponible.jpg";
                $record_item->image_medium = "imagen-no-disponible.jpg";
                $record_item->image_small = "imagen-no-disponible.jpg";
                $record_item->stock = 0;
                $record_item->stock_min = 1;
                $record_item->percentage_perception = 0;
                $record_item->active = 1;
                $record_item->status = 1;
//                \Log::debug("C");
//                \Log::debug($record_item);
                $record_item->save();
                $item['item_id'] = $record_item->id;
//                \Log::debug("D");
//                \Log::debug($item);
//                $record_item = Item::where('internal_id', $item['code'])->firstOrFail();
            }
//            \Log::debug($item);
//            $record_item = Item::find($item['item_id']);
//            \Log::debug($record_item);
            $json_item = [
                'name' => $record_item->name,
                'description' => $record_item->description,
                'internal_id' => $record_item->internal_id,
                'unit_type' => (key_exists('item', $item)) ? $item['item']['unit_type'] : $record_item->unit_type,
                'unit_type_id' => (key_exists('item', $item)) ? $item['item']['unit_type_id'] : $record_item->unit_type_id,
                'presentation' => (key_exists('item', $item)) ? (isset($item['item']['presentation']) ? $item['item']['presentation'] : []) : [],
                'amount_plastic_bag_taxes' => $record_item->amount_plastic_bag_taxes ? $record_item->amount_plastic_bag_taxes : 0,
                'is_set' => $record_item->is_set,
                'lots' => (isset($item['item']['lots'])) ? $item['item']['lots'] : [],
                'IdLoteSelected' => (isset($item['IdLoteSelected']) ? $item['IdLoteSelected'] : null),
                'discount_type' => (isset($item['discount_type']) ? $item['discount_type'] : null)
            ];
//            \Log::debug($record_item);
//            \Log::debug($item);
//            \Log::debug($json_item);
//            \Log::debug(array_merge($item, $json_item));
//\Log::debug($item);
//\Log::debug($record_item);
            $document->items()->create([
                'document_id' => $document->id,
                'item_id' => key_exists('item_id', $item) ? $item['item_id'] : $record_item->id,
                'item' => array_merge($item, $json_item),
                'unit_type_id' => (key_exists('item', $item)) ? $item['item']['unit_type_id'] : $record_item->unit_type_id,
                'quantity' => floatval((key_exists('quantity', $item)) ? $item['quantity'] : $item['invoiced_quantity']),
                'unit_price' => floatval(isset($item['price']) ? $item['price'] : $record_item->sale_unit_price),
                'tax_id' => isset($item['tax_id']) ? $item['tax_id'] : $record_item->tax_id,
                'tax' => Tax::find(isset($item['tax_id']) ? $item['tax_id'] : $record_item->tax_id),
                'total_tax' => isset($item['total_tax']) ? $item['total_tax'] : $item['price_amount'] - $item['line_extension_amount'],
                'subtotal' => floatval(isset($item['subtotal']) ? $item['subtotal'] : $item['line_extension_amount']),
                'discount' => isset($item['total_discount']) ? $item['total_discount'] : 0,
                'total' => isset($item['total']) ? $item['total'] : (isset($item['subtotal']) ? $item['subtotal'] : $item['line_extension_amount']) + (isset($item['total_tax']) ? $item['total_tax'] : $item['price_amount'] - $item['line_extension_amount']),
                'total_plastic_bag_taxes' => 0,
                'warehouse_id' => $item['warehouse_id'] ?? null,
                'from_remission' => $document->remission_id,// Agregamos esta bandera

            ]);
//            \Log::debug("E");
//\Log::debug("7");
        }
//\Log::debug("8");

        





        return $document;
    }

    public function savePayments($document, $payments){

        if($payments){

            $total = $document->total;
            $balance = $total - collect($payments)->sum('payment');

            $search_cash = ($balance < 0) ? collect($payments)->firstWhere('payment_method_type_id', '01') : null;

            $this->apply_change = false;

            if($balance < 0 && $search_cash){

                $payments = collect($payments)->map(function($row) use($balance){

                    $change = null;
                    $payment = $row['payment'];

                    if($row['payment_method_type_id'] == '01' && !$this->apply_change){

                        $change = abs($balance);
                        $payment = $row['payment'] - abs($balance);
                        $this->apply_change = true;

                    }

                    return [
                        "id" => null,
                        "document_id" => null,
                        "sale_note_id" => null,
                        "date_of_payment" => $row['date_of_payment'],
                        "payment_method_type_id" => $row['payment_method_type_id'],
                        "reference" => $row['reference'],
                        "payment_destination_id" => isset($row['payment_destination_id']) ? $row['payment_destination_id'] : null,
                        "change" => $change,
                        "payment" => $payment
                    ];

                });
            }

            // dd($payments, $balance, $this->apply_change);

            foreach ($payments as $row) {

                if($balance < 0 && !$this->apply_change){
                    $row['change'] = abs($balance);
                    $row['payment'] = $row['payment'] - abs($balance);
                    $this->apply_change = true;
                }

                $record = $document->payments()->create($row);

                //considerar la creacion de una caja chica cuando recien se crea el cliente
                if(isset($row['payment_destination_id'])){
                    $this->createGlobalPayment($record, $row);
                }

            }
        }
    }

    public static function getOrderReference($request)
    {
        $order_reference = null;

        if ($request->order_reference)
        {
            if (isset($request['order_reference']['issue_date_order']) && isset($request['order_reference']['id_order']))
            {
                $order_reference = [
                    'id_order' => $request['order_reference']['id_order'],
                    'issue_date_order' => $request['order_reference']['issue_date_order'],
                ];
            }
        }
        return $order_reference;
    }

    public static function getHealthFields($request)
    {
        $rawHealthFields = $request->health_fields ?? null;

        if (empty($rawHealthFields)) {
            return null;
        }

        if ($rawHealthFields instanceof \Illuminate\Support\Collection) {
            $rawHealthFields = $rawHealthFields->toArray();
        }

        if (is_string($rawHealthFields)) {
            $decoded = json_decode($rawHealthFields, true);
            $rawHealthFields = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($rawHealthFields)) {
            return null;
        }

        if (!isset($rawHealthFields['invoice_period_start_date']) || !isset($rawHealthFields['invoice_period_end_date'])) {
            return null;
        }

        $usersSource = $request->health_users ?? ($rawHealthFields['users_info'] ?? []);
        if ($usersSource instanceof \Illuminate\Support\Collection) {
            $usersSource = $usersSource->toArray();
        }

        $usersSource = is_array($usersSource) ? $usersSource : [];

        // Normalizar estructura con nombres y atributos requeridos por PDF/XML
        $normalized_users = [];

        $docTypeMap = [
            'CC' => 1,
            'CE' => 2,
            'TI' => 3,
            'PA' => 4,
            'RC' => 5,
            'MS' => 6,
            'AS' => 7,
        ];

        foreach ($usersSource as $user) {
            if ($user instanceof \Illuminate\Support\Collection) {
                $user = $user->toArray();
            }

            if (is_object($user)) {
                $user = json_decode(json_encode($user), true);
            }

            if (!is_array($user)) {
                continue;
            }

            $first = function (array $keys, array $src) {
                foreach ($keys as $key) {
                    if (isset($src[$key]) && $src[$key] !== '' && $src[$key] !== null) {
                        return $src[$key];
                    }
                }

                return null;
            };

            $docType = $first([
                'health_type_document_identification_id',
                'document_id_type_id',
                'user_type_document_id',
                'type_document_id',
            ], $user);

            if ($docType === null) {
                $docTypeCode = $first(['tipo_documento', 'type_document'], $user);
                if (is_string($docTypeCode) && isset($docTypeMap[$docTypeCode])) {
                    $docType = $docTypeMap[$docTypeCode];
                }
            }

            $identNumber = $first([
                'identification_number',
                'user_document_number',
                'documento',
                'document_number',
            ], $user);

            $firstName = $first(['first_name', 'primer_nombre', 'nombres', 'user_first_name'], $user);
            $middleName = $first(['middle_name', 'segundo_nombre'], $user);
            $surname = $first(['surname', 'primer_apellido', 'last_name', 'user_last_name'], $user);
            $secondSurname = $first(['second_surname', 'segundo_apellido', 'second_last_name'], $user);

            if (!$middleName && isset($user['user_first_name'])) {
                $parts = preg_split('/\s+/', trim((string) $user['user_first_name']));
                if (count($parts) > 1) {
                    $firstName = $parts[0];
                    $middleName = implode(' ', array_slice($parts, 1));
                }
            }

            if (!$secondSurname && isset($user['user_last_name'])) {
                $parts = preg_split('/\s+/', trim((string) $user['user_last_name']));
                if (count($parts) > 1) {
                    $surname = $parts[0];
                    $secondSurname = implode(' ', array_slice($parts, 1));
                }
            }

            $normalized = [
                'health_type_document_identification_id' => $docType !== null ? (int) $docType : null,
                'document_id_type_id' => $docType !== null ? (int) $docType : null,
                'identification_number' => $identNumber !== null ? (string) $identNumber : '',
                'first_name' => $firstName !== null ? (string) $firstName : '',
                'middle_name' => $middleName !== null ? (string) $middleName : '',
                'surname' => $surname !== null ? (string) $surname : '',
                'second_surname' => $secondSurname !== null ? (string) $secondSurname : '',
            ];

            $typeUser = $first(['health_type_user_id', 'type_user_id', 'user_type_id'], $user);
            if ($typeUser !== null) {
                $normalized['health_type_user_id'] = (int) $typeUser;
                $normalized['type_user_id'] = (int) $typeUser;
            }

            $contractMethod = $first([
                'health_contracting_payment_method_id',
                'contract_method_id',
                'method_id',
            ], $user);

            $contractMethodId = null;
            if ($contractMethod !== null) {
                $contractMethodId = (int) $contractMethod;
                $normalized['health_contracting_payment_method_id'] = $contractMethodId;
                $normalized['contract_method_id'] = $contractMethodId;
            }

            $normalized['user_payment_code'] = self::mapHealthPaymentCode($user, $contractMethodId);

            $coverage = $first([
                'health_coverage_id',
                'coverage_type_id',
                'coverage_id',
            ], $user);
            if ($coverage !== null) {
                $normalized['health_coverage_id'] = (int) $coverage;
                $normalized['coverage_type_id'] = (int) $coverage;
            }

            if (isset($user['provider_code'])) {
                $normalized['provider_code'] = (string) $user['provider_code'];
            }
            if (isset($user['contract_number'])) {
                $normalized['contract_number'] = (string) $user['contract_number'];
            }
            if (isset($user['policy_number'])) {
                $normalized['policy_number'] = (string) $user['policy_number'];
            }
            if (isset($user['co_payment'])) {
                $normalized['co_payment'] = $user['co_payment'];
            }
            if (isset($user['moderating_fee'])) {
                $normalized['moderating_fee'] = $user['moderating_fee'];
            }
            if (isset($user['shared_payment'])) {
                $normalized['shared_payment'] = $user['shared_payment'];
            }
            if (isset($user['advance_payment'])) {
                $normalized['advance_payment'] = $user['advance_payment'];
            }
            if (isset($user['autorization_numbers'])) {
                $normalized['autorization_numbers'] = $user['autorization_numbers'];
            }
            if (isset($user['mipres'])) {
                $normalized['mipres'] = $user['mipres'];
            }

            $normalized_users[] = $normalized;
        }

        if (empty($normalized_users)) {
            return null;
        }

        $validatorPayload = [
            'invoice_period_start_date' => $rawHealthFields['invoice_period_start_date'],
            'invoice_period_end_date' => $rawHealthFields['invoice_period_end_date'],
            'health_type_operation_id' => $rawHealthFields['health_type_operation_id'] ?? 1,
            'users_info' => array_map(function (array $user) {
                $firstNames = trim(implode(' ', array_filter([
                    $user['first_name'] ?? '',
                    $user['middle_name'] ?? '',
                ])));

                $lastNames = trim(implode(' ', array_filter([
                    $user['surname'] ?? '',
                    $user['second_surname'] ?? '',
                ])));

                return [
                    'user_type_document_id' => (int) ($user['health_type_document_identification_id'] ?? 1),
                    'user_document_number' => (string) ($user['identification_number'] ?? ''),
                    'user_first_name' => $firstNames,
                    'user_last_name' => $lastNames,
                    'user_contract_code' => (string) ($user['contract_number'] ?? $user['provider_code'] ?? 'DEFAULT'),
                    'user_payment_code' => (string) ($user['user_payment_code'] ?? self::mapHealthPaymentCode($user)),
                ];
            }, $normalized_users),
        ];

        $healthValidator = app(HealthFieldsValidatorService::class);
        $validated = $healthValidator->validateAndTransform($validatorPayload);

        foreach ($validated['users_info'] as $index => $validatedUser) {
            $normalized_users[$index] = array_merge($normalized_users[$index] ?? [], $validatedUser);
        }

        return [
            'invoice_period_start_date' => $validated['invoice_period_start_date'],
            'invoice_period_end_date' => $validated['invoice_period_end_date'],
            'health_type_operation_id' => $validated['health_type_operation_id'],
            'users_info' => array_values($normalized_users),
        ];
    }

    private static function mapHealthPaymentCode(array $source, ?int $methodId = null): string
    {
        $candidates = [
            'user_payment_code',
            'payment_code',
            'payment_method_code',
            'paymentMethodCode',
        ];

        foreach ($candidates as $key) {
            if (isset($source[$key])) {
                $raw = trim((string) $source[$key]);
                if ($raw !== '') {
                    if (ctype_digit($raw) && strlen($raw) === 1) {
                        $raw = str_pad($raw, 2, '0', STR_PAD_LEFT);
                    }

                    return substr($raw, 0, 2);
                }
            }
        }

        if ($methodId === null) {
            $methodId = (int) ($source['health_contracting_payment_method_id']
                ?? $source['contract_method_id']
                ?? $source['method_id']
                ?? 1);
        }

        $safeMap = [
            1 => '01',
            2 => '01',
            3 => '01',
            4 => '01',
            5 => '01',
        ];

        return $safeMap[$methodId] ?? '01';
    }

    /**
     * Genera un arreglo con la data necesaria para insertar en el detalle del documento
     *
     * Usado en:
     * RemissionController
     *
     * @param  array $inputs
     * @return array
    */
    public static function getDataItemFromInputs($inputs)
    {

        $items = [];

        foreach ($inputs['items'] as $item) {

            $json_item = [
                'name' => $item['item']['name'],
                'description' => $item['item']['description'],
                'internal_id' => $item['item']['internal_id'],

                'unit_type' => $item['item']['unit_type'],
                'unit_type_id' => $item['item']['unit_type_id'],
                'presentation' => (key_exists('item', $item)) ? (isset($item['item']['presentation']) ? $item['item']['presentation']:[]):[],

                'is_set' => (isset($item['item']['is_set'])) ? $item['item']['is_set']:[],
                'lots' => (isset($item['item']['lots'])) ? $item['item']['lots']:[],
                'IdLoteSelected' => ( isset($item['IdLoteSelected']) ? $item['IdLoteSelected'] : null )
            ];

            $items [] = [
                'item_id' => $item['item_id'],
                // 'item' => array_merge($item, $json_item),
                'item' => $json_item,
                'unit_type_id' => $item['unit_type_id'],
                'quantity' => $item['quantity'],
                'unit_price' => isset($item['price']) ? $item['price'] : $item['unit_price'],
                'tax_id' => $item['tax_id'],
                'tax' => Tax::find($item['tax_id']),
                'total_tax' => $item['total_tax'],
                'subtotal' => $item['subtotal'],
                'discount' => $item['discount'],
                'total' => $item['total'],
                'warehouse_id' => $item['warehouse_id'] ?? null,
            ];

        }

        return $items;
    }


    /**
     *
     * Actualizar mensaje de respuesta al consultar zipkey
     *
     * @param  string $response_message_query_zipkey
     * @param  Document $document
     * @return void
     */
    public function updateMessageQueryZipkey($response_message_query_zipkey, Document $document)
    {
        $document->update([
            'response_message_query_zipkey' => $response_message_query_zipkey
        ]);
    }

    /**
     *
     * Actualizar estado dependiendo de la validación al enviar a la dian
     *
     * @param  int $state_document_id
     * @param  Document $document
     * @return void
     */
    public function updateStateDocument($state_document_id, Document $document)
    {
        $document->update([
            'state_document_id' => $state_document_id
        ]);
    }

    /**
     *
     * @param  bool $success
     * @param  string $message
     * @return array
     */
    public function responseMessage($success, $message)
    {
        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    public function throwException($message)
    {
        throw new Exception($message);
    }

    /**
     *
     * Aplicar formato
     *
     * @param  $value
     * @param  int $decimals
     * @return string
     */
    public static function applyNumberFormat($value, $decimals = 2)
    {
        return number_format($value, $decimals, ".", "");
    }

}
