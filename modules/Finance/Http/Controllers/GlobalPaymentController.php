<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Finance\Models\GlobalPayment;
use App\Models\Tenant\Cash;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\Company;
use Modules\Finance\Traits\FinanceTrait;
use Modules\Finance\Http\Resources\GlobalPaymentCollection;
use Modules\Finance\Exports\GlobalPaymentExport;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Tenant\Establishment;
use Carbon\Carbon;

class GlobalPaymentController extends Controller
{

    use FinanceTrait;

    public function index(){

        return view('finance::global_payments.index');
    }


    public function filter(){

        $payment_types = $this->getCollectionPaymentTypes();
        $destination_types = $this->getCollectionDestinationTypes();
        $currencies = $this->getCurrencies();

        return compact('payment_types', 'destination_types', 'currencies');
    }


    public function records(Request $request)
    {

        // dd($request->all());
        $records = $this->getRecords($request->all(), GlobalPayment::class);

        // Obtener todos los registros y ordenarlos por fecha de pago en PHP
        $collection = $records->get()->sortByDesc(function($item) {
            return $item->payment->date_of_payment ?? null;
        })->values();

        // Paginar manualmente la colección ordenada
        $perPage = config('tenant.items_per_page');
        $page = request('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return new GlobalPaymentCollection($paginated);

    }

    public function getRecords($request, $model){

        $data_of_period = $this->getDatesOfPeriod($request);
        $payment_type = $request['payment_type'];
        $destination_type = $request['destination_type'];

        $params = (object)[
            'date_start' => $data_of_period['d_start'],
            'date_end' => $data_of_period['d_end'],
            'currency_id' => $request['currency_id'],
        ];

        $records = $model::whereFilterPaymentType($params);

        if($payment_type){
            $records = $records->wherePaymentType($payment_type);
        }

        if($destination_type){
            $records = $records->whereDestinationType($destination_type);
        }

        return $records->latest();
    }


    public function pdf(Request $request) {

        $company = Company::first();
        $establishment = ($request->establishment_id) ? Establishment::findOrFail($request->establishment_id) : auth()->user()->establishment;
        $records = $this->getRecords($request->all(), GlobalPayment::class)->get();

        $pdf = PDF::loadView('finance::global_payments.report_pdf', compact("records", "company", "establishment"))->setPaper('a4', 'landscape');;

        $filename = 'Reporte_Pagos_'.date('YmdHis');

        return $pdf->download($filename.'.pdf');
    }


    public function excel(Request $request) {

        $company = Company::first();
        $establishment = ($request->establishment_id) ? Establishment::findOrFail($request->establishment_id) : auth()->user()->establishment;
        $records = $this->getRecords($request->all(), GlobalPayment::class)->get();
        // dd($records);

        return (new GlobalPaymentExport)
                ->records($records)
                ->company($company)
                ->establishment($establishment)
                ->download('Reporte_Pagos_'.Carbon::now().'.xlsx');

    }

}
