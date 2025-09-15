<?php

namespace App\Http\Middleware;

use Closure;
use Modules\Factcolombia1\Models\System\Company;
use Modules\Factcolombia1\Models\Tenant\User;
use App\Models\Tenant\Document;
use App\Models\Tenant\DocumentPos;
use Carbon\Carbon;
use Hyn\Tenancy\Environment;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Models\DocumentPayroll;
use Modules\Sale\Models\Remission;
use Modules\Purchase\Models\SupportDocument;
class CheckTenantLimits
{
    public function handle($request, Closure $next)
    {
        // ----------- VALIDACIÓN GLOBAL DE DOCUMENTOS -----------
        $global_limit = config('app.limit_documents', 0);
        if (
            (
                $request->is('co-documents') || $request->is('co-documents/*') ||
                $request->is('document-pos') || $request->is('document-pos/*') ||
                $request->is('support-documents') || $request->is('support-documents/*') ||
                $request->is('payroll/document-payrolls') || $request->is('payroll/document-payrolls/*')
            )
            && $global_limit != 0
        ) {
            $total = 0;
            $companies = Company::all();
            $startMonth = Carbon::now()->startOfMonth()->toDateString();
            $endMonth = Carbon::now()->endOfMonth()->toDateString();

            foreach ($companies as $company) {
                $tenancy = app(Environment::class);
                $tenancy->tenant($company->hostname->website);

                $count_documents = DB::connection('tenant')->table('documents')
                    ->where('state_document_id', 5)
                    ->whereBetween('date_of_issue', [$startMonth, $endMonth])
                    ->count();

                $count_documents_pos = DB::connection('tenant')->table('documents_pos')
                    ->where('state_type_id', 1)
                    ->whereBetween('date_of_issue', [$startMonth, $endMonth])
                    ->count();

                $count_support_documents = DB::connection('tenant')->table('co_support_documents')
                    ->where('state_document_id', 5)
                    ->whereBetween('date_of_issue', [$startMonth, $endMonth])
                    ->count();

                $count_payroll_documents = DB::connection('tenant')->table('co_document_payrolls')
                    ->where('state_document_id', 5)
                    ->whereBetween('date_of_issue', [$startMonth, $endMonth])
                    ->count();

                $total += $count_documents + $count_documents_pos + $count_support_documents + $count_payroll_documents;
            }

            if ($total >= $global_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se ha alcanzado el límite mensual de documentos permitidos para todas las empresas. Por favor, contacte al administrador.'
                ], 403);
            }
        }
        // ----------- FIN VALIDACIÓN GLOBAL -----------

        $hostname = app(\Hyn\Tenancy\Contracts\CurrentHostname::class);
        $company = null;
        if ($hostname) {
            $company = Company::where('hostname_id', $hostname->id)->first();
        }

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la empresa para este tenant.'
            ], 403);
        }
        if (!$company->plan_started_at || !$company->plan_expires_at || !$company->isPlanActive()) {
            return response()->json([
                'success' => false,
                'message' => 'La empresa no tiene un plan activo. Por favor, contacte al administrador.'
            ], 403);
        }
        // Validar límite de usuarios
        if ($request->is('users*')&& $company->locked_users == 1) {
            $isCreate = $request->isMethod('post') && !$request->input('id');
            if ($isCreate) {
                $limit_users = $company->limit_users;
                $start = $company->plan_started_at;
                $end = $company->plan_expires_at;

                $current_users = User::whereBetween('created_at', [$start, $end])->count();
                if ($limit_users != 0 && $current_users >= $limit_users) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Has excedido el límite de usuarios permitidos por tu plan.'
                    ], 403);
                }
            }
        }

        if (
            (
            $request->is('co-documents') || $request->is('co-documents/*') ||
            $request->is('document-pos') || $request->is('document-pos/*') ||
            $request->is('support-documents') || $request->is('support-documents/*') ||
            $request->is('payroll/document-payrolls') || $request->is('payroll/document-payrolls/*')
            )
            && $company->locked_emission == 1
        ) {
            $limit_documents = $company->limit_documents;
            $start = $company->plan_started_at;
            $end = $company->plan_expires_at;

            $current_documents = Document::where('state_document_id', 5)
                ->whereBetween('date_of_issue', [$start, $end])
                ->count();

            $current_documents_pos = DocumentPos::where('state_type_id', 1)
                ->whereBetween('date_of_issue', [$start, $end])
                ->count();

            $current_support_documents = SupportDocument::where('state_document_id', 5)
                ->whereBetween('date_of_issue', [$start, $end])
                ->count();

            $current_payroll_documents = DocumentPayroll::where('state_document_id', 5)
                ->whereBetween('date_of_issue', [$start, $end])
                ->count();

            $total_documents = $current_documents + $current_documents_pos + $current_support_documents + $current_payroll_documents;

            if ($limit_documents != 0 && $total_documents >= $limit_documents) {
                // \Log::warning('Límite de documentos excedido');
                return response()->json([
                    'success' => false,
                    'message' => 'Has excedido el límite de documentos permitidos por tu plan.'
                ], 403);
            }
        }

        return $next($request);
    }
}