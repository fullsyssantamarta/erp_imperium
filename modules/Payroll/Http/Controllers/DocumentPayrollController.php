<?php

namespace Modules\Payroll\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Payroll\Models\{
    DocumentPayroll,
    Worker
};
use Modules\Payroll\Http\Resources\{
    DocumentPayrollCollection,
    DocumentPayrollResource
};
use Modules\Payroll\Http\Requests\DocumentPayrollRequest;
use Modules\Factcolombia1\Models\TenantService\{
    PayrollPeriod,
    TypeLawDeductions,
    TypeDisability,
    AdvancedConfiguration,
    TypeOvertimeSurcharge,
};
use Modules\Factcolombia1\Models\Tenant\{
    PaymentMethod,
    TypeDocument,
};
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Payroll\Helpers\DocumentPayrollHelper;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Payroll\Traits\UtilityTrait;


class DocumentPayrollController extends Controller
{

    use UtilityTrait;

    public function index()
    {
        return view('payroll::document-payrolls.index');
    }

    public function create()
    {
        return view('payroll::document-payrolls.form');
    }

    public function columns()
    {
        return [
            'consecutive' => 'Número',
            'date_of_issue' => 'Fecha de emisión',
        ];
    }

    public function tables()
    {
        return [
            'workers' => $this->table('workers'),
            'payroll_periods' => PayrollPeriod::get(),
            'type_disabilities' => TypeDisability::get(),
            'payment_methods' => PaymentMethod::get(),
            'type_law_deductions' => TypeLawDeductions::whereTypeLawDeductionsWorker()->get(),
            'advanced_configuration' => AdvancedConfiguration::first(),
            // 'type_documents' => TypeDocument::get(),
            'resolutions' => TypeDocument::select('id','prefix', 'resolution_number')->where('code', 9)->get()
        ];
    }

    public function table($table)
    {

        if($table == 'workers')
        {
            return Worker::take(20)->get()->transform(function($row){
                return $row->getSearchRowResource();
            });
        }

        if($table == 'type_overtime_surcharges')
        {
            return TypeOvertimeSurcharge::get();
        }

        return [];
    }


    public function record($id)
    {
        return new DocumentPayrollResource(DocumentPayroll::findOrFail($id));
    }


    public function records(Request $request)
    {
        $records = DocumentPayroll::whereFilterRecords($request)->latest();

        return new DocumentPayrollCollection($records->paginate(config('tenant.items_per_page')));
    }


    /**
     * Consultar zipkey - usado en habilitación
     *
     * @param  Request $request
     * @return array
     */
    public function queryZipkey(Request $request)
    {

        try {

            $document = DocumentPayroll::findOrFail($request->id);
            $helper = new DocumentPayrollHelper();
            $zip_key = $document->response_api->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
            // dd($document);

            return $helper->validateZipKey($zip_key, $document->number_full, $document);


        } catch (Exception $e)
        {
            return $this->getErrorFromException($e->getMessage(), $e);
        }

    }

    protected function processAccruedData($accrued)
    {
        $processedAccrued = $accrued;
        
        if (isset($processedAccrued['transportation_allowance']) && 
            ($processedAccrued['transportation_allowance'] == 0 || 
             $processedAccrued['transportation_allowance'] === null)) {
            unset($processedAccrued['transportation_allowance']);
        }
        
        return $processedAccrued;
    }

    public function store(DocumentPayrollRequest $request)
    {
        try {

            $data = DB::connection('tenant')->transaction(function () use($request) {
                $documents = [];
                $workers = $request->worker_id;
                $base_request = $request->all();

                foreach ($workers as $worker_id) {
                    $worker_model = Worker::find($worker_id);
                    
                    // Clonar datos base del request para cada trabajador
                    $worker_request = $base_request;
                    
                    // Actualizar datos específicos del trabajador
                    $worker_request['worker_id'] = $worker_id;
                    $worker_request['payment'] = [
                        'bank_name' => $worker_model->payment->bank_name,
                        'account_type' => $worker_model->payment->account_type,
                        'account_number' => $worker_model->payment->account_number,
                        'payment_method_id' => $worker_model->payment->payment_method_id,
                    ];

                    // Actualizar salario y cálculos relacionados para este trabajador específico
                    $days_worked = $worker_request['accrued']['worked_days'] ?? 0;
                    $daily_salary = $worker_model->salary / 30;
                    $proportional_salary = round($daily_salary * $days_worked, 2);

                    $worker_request['accrued']['total_base_salary'] = $proportional_salary;
                    $worker_request['accrued']['salary'] = $proportional_salary;
                                        
                    // Recalcular total de devengados basado en el salario del trabajador actual
                    $transportation_allowance = $worker_request['accrued']['transportation_allowance'] ?? 0;
                    $worker_request['accrued']['accrued_total'] = $proportional_salary + $transportation_allowance;


                    // Crear nuevo request con los datos actualizados
                    $newRequest = new DocumentPayrollRequest();
                    $newRequest->merge($worker_request);

                    // inputs
                    $helper = new DocumentPayrollHelper();
                    $inputs = $helper->getInputs($newRequest);

                    // Procesar los datos de accrued antes de guardar
                    if (isset($inputs['accrued'])) {
                        $inputs['accrued'] = $this->processAccruedData($inputs['accrued']);
                    }

                    // registrar nomina en bd
                    $document = DocumentPayroll::create($inputs);
                    $document->accrued()->create($inputs['accrued']);
                    $document->deduction()->create($inputs['deduction']);

                    // enviar nomina a la api
                    $send_to_api = $helper->sendToApi($document, $inputs);

                    $document->update([
                        'response_api' => $send_to_api
                    ]);
                    $documents[] = $document->id;
                }

                return [
                    'documents' => $documents,
                    'total' => count($documents)
                ];
            });

            return [
                'success' => true,
                'message' => 'Nómina registrada con éxito',
                'data' => $data
            ];

        } catch (Exception $e)
        {
            return $this->getErrorFromException($e->getMessage(), $e);
        }

    }


    /**
     * Descarga de xml/pdf
     *
     * @param  string $filename
     */
    public function downloadFile($filename)
    {
        return app(DocumentController::class)->downloadFile($filename);
    }


    /**
     * Envio de correo de la nómina
     *
     * @param  Request $request
     * @return array
     */
    public function sendEmail(Request $request)
    {
        return (new DocumentPayrollHelper())->sendEmail($request);
    }

    public function preeliminarview(Request $request)
    {
        try {
            // Validación básica
            if (empty($request->worker_id)) {
                throw new Exception('Debe seleccionar un trabajador para la vista previa');
            }
            if (empty($request->type_document_id)) {
                throw new Exception('Debe seleccionar una Resolucion de documento');
            }

            $worker_id = is_array($request->worker_id) ? $request->worker_id[0] : $request->worker_id;
            $worker = Worker::findOrFail($worker_id);
            
            // Obtener resolución
            $resolution = TypeDocument::where('id', $request->type_document_id)
                                    ->where('code', 9)
                                    ->firstOrFail();

            // Preparar datos para la API usando DTO
            $previewData = $this->preparePreviewData($worker, $resolution, $request);

            // Enviar a API y obtener respuesta
            $helper = new DocumentPayrollHelper();
            $response = $helper->sendToPreviewApi($previewData);

            return [
                'success' => true,
                'message' => $response['message'],
                'base64payrollpdf' => $response['base64payrollpdf']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function preparePreviewData($worker, $resolution, $request) 
    {
        $helper = new DocumentPayrollHelper();
        $next_consecutive = $helper->getConsecutive(9, true, $resolution->prefix);

        if (!$next_consecutive) {
            throw new Exception('Error al obtener el consecutivo');
        }

        return [
            'type_document_id' => 9,
            'resolution_number' => $resolution->resolution_number,
            'consecutive' => $next_consecutive,
            'prefix' => $resolution->prefix,
            'payroll_period_id' => $request->payroll_period_id,
            'payment_dates' => $request->payment_dates,
            'worker_code' => $worker->code,
            'worker' => [
                'type_worker_id' => (int)$worker->type_worker_id,
                'sub_type_worker_id' => (int)$worker->sub_type_worker_id,
                'payroll_type_document_identification_id' => (int)$worker->payroll_type_document_identification_id,
                'municipality_id' => (int)$worker->municipality_id,
                'type_contract_id' => (int)$worker->type_contract_id,
                'high_risk_pension' => (bool)$worker->high_risk_pension,
                'identification_number' => $worker->identification_number,
                'surname' => $worker->surname,
                'second_surname' => $worker->second_surname,
                'first_name' => $worker->first_name,
                'address' => $worker->address,
                'integral_salarary' => (bool)$worker->integral_salarary,
                'salary' => (float)$worker->salary
            ],
            'payment' => $request->payment ?? [
                'payment_method_id' => (int)($worker->payment->payment_method_id ?? 1),
                'bank_name' => $worker->payment->bank_name ?? null,
                'account_type' => $worker->payment->account_type ?? null, 
                'account_number' => $worker->payment->account_number ?? null
            ],
            'period' => $request->period ?? [
                'admision_date' => $worker->work_start_date ?? date('Y-m-d'),
                'settlement_start_date' => date('Y-m-01'),
                'settlement_end_date' => date('Y-m-t'),
                'worked_time' => 30,
                'issue_date' => date('Y-m-d')
            ],
            'accrued' => $request->accrued ?? [
                'worked_days' => 30,
                'salary' => (float)$worker->salary,
                'accrued_total' => (float)$worker->salary,
                'transportation_allowance' => null
            ],
            'deductions' => $request->deduction ?? [
                'eps_type_law_deductions_id' => 1,
                'eps_deduction' => 0,
                'pension_type_law_deductions_id' => 5,
                'pension_deduction' => 0,
                'deductions_total' => 0,
                'fondossp_type_law_deductions_id' => null,
                'fondosp_deduction_SP' => null,
                'fondossp_sub_type_law_deductions_id' => null,
                'fondosp_deduction_sub' => null,
                'labor_union' => [],
                'sanctions' => [],
                'orders' => [],
                'third_party_payments' => [],
                'advances' => [],
                'voluntary_pension' => null,
                'withholding_at_source' => null,
                'afc' => null,
                'cooperative' => null,
                'tax_liens' => null,
                'supplementary_plan' => null,
                'other_deductions' => [],
                'education' => null,
                'refund' => null,
                'debt' => null
            ]
        ];
    }

}
