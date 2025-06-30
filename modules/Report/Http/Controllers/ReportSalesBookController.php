<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Report\Traits\ReportSalesBookTrait;
use Modules\Report\Exports\SaleBookExport;


class ReportSalesBookController extends Controller
{

    use ReportSalesBookTrait;


    public function index()
    {
        return view('report::co-sales-book.index');
    }


    /**
     *
     * @param  string $type
     * @param  Request $request
     * @return mixed
     */
    public function export($type, Request $request)
    {
        $request['summary_sales_book'] = $request->summary_sales_book === 'true';
        $company = Company::first();
        $establishment = $request->establishment_id != '' ? Establishment::find($request->establishment_id) : auth()->user()->establishment;
        $request->merge(['establishment_id' => $establishment->id]);
        $filters = $request;
        $data = $this->getData($request);
        $records = $data['records'];
        $taxes = $this->getTaxesDocuments($records);
        $summary_records = $request->summary_sales_book ? $this->getSummaryRecords($data, $request) : [];

        // NUEVO: Obtener tipos de retención únicos de todos los documentos
        $retention_types = collect();
        foreach ($records as $record) {
            $taxes_raw = json_decode($record->getRawTaxes(), true) ?? [];
            foreach ($taxes_raw as $tax) {
                if (isset($tax['is_retention']) && $tax['is_retention']) {
                    $retention_types->push([
                        'id' => $tax['id'],
                        'name' => $tax['name'],
                    ]);
                }
            }
        }
        $retention_types = $retention_types->unique('id')->values();

        $report_data = compact('records', 'company', 'establishment', 'filters', 'taxes', 'summary_records', 'retention_types');

        // Mostrar el campo taxes sin formateo ni filtro del primer documento (si existe)
        // if ($records->count() > 0) {
        //     $raw_taxes = $records->first()->getRawTaxes();
        //     dd($raw_taxes);
        // }

        switch ($type) {
            case 'excel':
                return (new SaleBookExport)
                    ->records($report_data)
                    ->download('Reporte_Libro_Ventas_'.date('YmdHis').'.xlsx');
                break;
            default:
                $pdf = PDF::loadView('report::co-sales-book.report_pdf', $report_data)->setPaper('a4', 'landscape');
                $filename = 'Reporte_Libro_Ventas_'.date('YmdHis');
                return $pdf->stream($filename.'.pdf');
                break;
        }
    }

}
