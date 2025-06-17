<?php

namespace Modules\Payroll\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Payroll\Models\{
    DocumentPayrollAdjustNote,
    DocumentPayroll,
    Worker
};
use Modules\Payroll\Http\Resources\{
    DocumentPayrollAdjustNoteResource
};
use Modules\Payroll\Http\Requests\DocumentPayrollAdjustNoteRequest;
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


class DocumentPayrollAdjustNoteController extends Controller
{
    
    use UtilityTrait;

    public function create($id)
    {
        $type_payroll_adjust_note_id = DocumentPayrollAdjustNote::ADJUST_NOTE_REPLACE_ID;

        return view('payroll::document-payrolls.form', compact('id', 'type_payroll_adjust_note_id'));
    }

 
    public function tables($type_payroll_adjust_note_id)
    {

        $resolutions = TypeDocument::select('id', 'prefix', 'resolution_number')->where('code', DocumentPayroll::ADJUST_NOTE_TYPE_DOCUMENT_ID)->get();

        // nomina eliminacion
        if($type_payroll_adjust_note_id == DocumentPayrollAdjustNote::ADJUST_NOTE_ELIMINATION_ID)
        {
            return [
                'resolutions' => $resolutions
            ];
        }

        // nomina de reemplazo
        
        return [
            'workers' => [],
            'payroll_periods' => PayrollPeriod::get(),
            'type_disabilities' => TypeDisability::get(),
            'payment_methods' => PaymentMethod::get(),
            'type_law_deductions' => TypeLawDeductions::whereTypeLawDeductionsWorker()->get(),
            'advanced_configuration' => AdvancedConfiguration::first(),
            'resolutions' => $resolutions
        ];

    }
        
    /**
     * Buscar nómina afectada
     *
     * @param  int $id
     * @return DocumentPayrollAdjustNoteResource
     */
    public function record($id)
    {
        $document = DocumentPayroll::with([
            'accrued', 
            'deduction',
            'worker',
            'payroll_period'
        ])->findOrFail($id);

        return new DocumentPayrollAdjustNoteResource($document);
    }
    
     
    /**
     * 
     * Registar nómina de eliminación/reemplazo
     *
     * @param  DocumentPayrollAdjustNoteRequest $request
     * @return array
     */
    public function store(DocumentPayrollAdjustNoteRequest $request)
    {
        try {
            // dd($request->all());
            DB::connection('tenant')->beginTransaction();
            
            // Obtener y validar inputs
            $helper = new DocumentPayrollHelper();
            $inputs = $helper->getInputsAdjustNote($request);
            
            // Procesar los datos de accrued antes de guardar
            if (isset($inputs['accrued'])) {
                $inputs['accrued'] = $this->processAccruedData($inputs['accrued']);
            }
            
            // Crear documento base
            $document = DocumentPayroll::create($inputs);
            
            // Crear nota de ajuste
            $document->adjust_note()->create($inputs['adjust_note']);

            // Si es nómina de reemplazo
            if(!$document->adjust_note->is_adjust_note_elimination) {
                $document->accrued()->create($inputs['accrued']);
                $document->deduction()->create($inputs['deduction']);
            }

            // Enviar a API
            $send_to_api = $helper->sendToApi($document, $inputs);
            
            // Actualizar respuesta
            $document->update([
                'response_api' => $send_to_api
            ]);

            DB::connection('tenant')->commit();
            
            return [
                'success' => true,
                'message' => $document->adjust_note->is_adjust_note_elimination ? 
                            "Nómina de eliminación {$document->number_full} registrada con éxito" : 
                            "Nómina de reemplazo {$document->number_full} registrada con éxito",
                'data' => [
                    'id' => $document->id
                ]
            ];

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            \Log::error($e->getMessage());
            return $this->getErrorFromException($e->getMessage(), $e);
        }
    }
 
        
}
