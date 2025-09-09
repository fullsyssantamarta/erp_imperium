<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Exports\AccountsReceivable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Dashboard\Helpers\DashboardData;
use Modules\Dashboard\Helpers\DashboardUtility;
use Modules\Dashboard\Helpers\DashboardSalePurchase;
use Modules\Dashboard\Helpers\DashboardView;
use Modules\Dashboard\Helpers\DashboardStock;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Document;
use App\Models\Tenant\Company;
use App\Models\Tenant\DocumentPos;
use Hyn\Tenancy\Contracts\CurrentHostname;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Arr;
use Modules\Payroll\Models\DocumentPayroll;
use Modules\Purchase\Models\SupportDocument;
use Modules\Sale\Models\Remission;

class DashboardController extends Controller
{
    public function index()
    {
        if(auth()->user()->type != 'admin' || !auth()->user()->searchModule('dashboard'))
            return redirect()->route('tenant.documents.index');

        $company = Company::select('soap_type_id')->first();
        $soap_company  = $company->soap_type_id;

        return view('dashboard::index', compact('soap_company'));
    }

    public function filter()
    {
        return [
            'establishments' => DashboardView::getEstablishments(),
            'currencies' => DashboardView::getCurrencies(),
        ];
    }

    public function data(Request $request)
    {
        return [
            'data' => (new DashboardData())->data($request->all()),
        ];
    }

    // public function unpaid(Request $request)
    // {
    //     return [
    //             'records' => (new DashboardView())->getUnpaid($request->all())
    //     ];
    // }

    // public function unpaidall()
    // {

    //     return Excel::download(new AccountsReceivable, 'Allclients.xlsx');

    // }

    public function data_aditional(Request $request)
    {
        return [
            'data' => (new DashboardSalePurchase())->data($request->all()),
        ];
    }

    public function stockByProduct(Request $request)
    {
        return  (new DashboardStock())->data($request);
    }


    public function utilities(Request $request)
    {
        return [
            'data' => (new DashboardUtility())->data($request->all()),
        ];
    }

    public function df()
    {
        $path = app_path();
        //df -m -h --output=used,avail,pcent /

        $used = new Process('df -m -h --output=used /');
        $used->run();
        if (!$used->isSuccessful()) {
            return ['error'];
            throw new ProcessFailedException($used);
        }
        $disc_used = $used->getOutput();
        $array[] = str_replace("\n","",$disc_used);

        $avail = new Process('df -m -h --output=avail /');
        $avail->run();
        if (!$avail->isSuccessful()) {
            return ['error'];
            throw new ProcessFailedException($avail);
        }
        $disc_avail = $avail->getOutput();
        $array[] = str_replace("\n","",$disc_avail);

        $pcent = new Process('df -m -h --output=pcent /');
        $pcent->run();
        if (!$pcent->isSuccessful()) {
            return ['error'];
            throw new ProcessFailedException($pcent);
        }
        $disc_pcent = $pcent->getOutput();
        $array[] = str_replace("\n","",$disc_pcent);

        return $array;


    }


    public function electronicConsumption(Request $request)
    {
        $company = \Modules\Factcolombia1\Models\System\Company::first();

        if (!$company || !$company->plan_started_at || !$company->plan_expires_at) {
            return response()->json(['success' => false, 'message' => 'Empresa o fechas de plan no encontradas'], 404);
        }

        $plan = $company->plan;
        $plan_status = ($plan && $company->isPlanActive()) ? 'Activo' : ((now() > $company->plan_expires_at) ? 'Vencido' : 'Por Vencer');
        $start = $company->plan_started_at;
        $end = $company->plan_expires_at;

        // Sumar todos los documentos aceptados
        $documents = [
            'Facturas electrónicas' => Document::where('state_document_id', 5)->whereBetween('date_of_issue', [$start, $end])->count(),
            'Ventas POS' => DocumentPos::where('state_type_id', 1)->whereBetween('date_of_issue', [$start, $end])->count(),
            'Documentos de soporte' => SupportDocument::where('state_document_id', 5)->whereBetween('date_of_issue', [$start, $end])->count(),
            'Nómina electrónica' => DocumentPayroll::where('state_document_id', 5)->whereBetween('date_of_issue', [$start, $end])->count(),
        ];

        $total_documents = array_sum($documents);

        return [
            'plan_name' => $plan ? $plan->name : 'Sin plan',
            'plan_status' => $plan_status,
            'plan_start' => $start ? date('Y-m-d', strtotime($start)) : null,
            'plan_end' => $end ? date('Y-m-d', strtotime($end)) : null,
            'plan_limit_documents' => $company->limit_documents,
            'documents' => $documents,
            'total_documents' => $total_documents
        ];
    }
}
