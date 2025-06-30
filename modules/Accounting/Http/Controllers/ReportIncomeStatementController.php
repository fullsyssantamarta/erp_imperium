<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ChartOfAccount;
use Mpdf\Mpdf;

/**
 * Class ReportIncomeStatementController
 * Reporte de Estado de Resultados
 */
class ReportIncomeStatementController extends Controller
{
    public function index()
    {
        return view('accounting::reports.income_statement');
    }

    public function records(Request $request)
    {
        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;

        //                                            ganancia / gastos / costos
        $accounts = ChartOfAccount::whereIn('type', ['Revenue', 'Expense', 'Cost'])
            ->with(['journalEntryDetails' => function ($query) use ($dateStart, $dateEnd) {
                // Filtrar los detalles por rango de fechas
                $query->whereHas('journalEntry', function ($subQuery) use ($dateStart, $dateEnd) {
                    if ($dateStart && $dateEnd) {
                        $subQuery->whereBetween('date', [$dateStart, $dateEnd]);
                    }
                });
                $query->selectRaw('chart_of_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->groupBy('chart_of_account_id');
            }])
            ->get()
            ->map(function ($account) {
                $debit = $account->journalEntryDetails->sum('total_debit');
                $credit = $account->journalEntryDetails->sum('total_credit');

                // Calculamos el saldo según el tipo de cuenta
                if ($account->type === 'Revenue') {
                    $saldo = $credit - $debit;
                } elseif ($account->type === 'Cost' || $account->type === 'Expense') {
                    $saldo = $debit - $credit;
                } else {
                    $saldo = 0;
                }

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'saldo' => $saldo,
                ];
            });


        // Separar las cuentas por tipo
        $revenues = $accounts->where('type', 'Revenue')->where('saldo', '>', 0);
        $costs = $accounts->where('type', 'Cost')->where('saldo', '>', 0);
        $expenses = $accounts->where('type', 'Expense')->where('saldo', '>', 0);

        // Ahora agrupamos por tipo:
        $totalRevenue = $accounts->where('type', 'Revenue')->sum('saldo');
        $totalCost    = $accounts->where('type', 'Cost')->sum('saldo');
        $totalExpense = $accounts->where('type', 'Expense')->sum('saldo');

        // ✅ Utilidades:
        $grossProfit     = $totalRevenue - $totalCost;         // Utilidad Bruta
        $operatingProfit = $grossProfit - $totalExpense;       // Utilidad Operativa
        $netProfit       = $operatingProfit;                   // Por ahora igual a operativa

        return response()->json([
            'revenues' => $revenues->values()->all(),
            'costs' => $costs->values()->all(),
            'expenses' => $expenses->values()->all(),
            'totals' => [
                'revenue' => $totalRevenue,
                'cost' => $totalCost,
                'expense' => $totalExpense,
            ],
            'gross_profit' => $grossProfit,
            'operating_profit' => $operatingProfit,
            'net_profit' => $netProfit,
        ]);
    }

    public function export(Request $request)
    {
        $format = $request->input('format', 'pdf');

        if ($format === 'pdf') {
            return $this->exportPdf($request);
        // } elseif ($format === 'excel') {
        //     return $this->exportExcel($request);
        } else {
            return response()->json(['error' => 'Formato no soportado'], 400);
        }
    }

    private function exportPdf(Request $request)
    {
        // Reutilizar la lógica de records para obtener los datos
        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;
        $data = $this->records($request)->getData(true);

        // Renderizar la vista como HTML
        $html = view('accounting::pdf.income_statement', [
            'revenues' => $data['revenues'],
            'costs' => $data['costs'],
            'expenses' => $data['expenses'],
            'gross_profit' => $data['gross_profit'],
            'operating_profit' => $data['operating_profit'],
            'net_profit' => $data['net_profit'],
            'totals' => $data['totals'],
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ])->render();

        // Configurar mPDF
        $mpdf = new Mpdf();
        $mpdf->SetHeader('Reporte de Estado de Resultados');
        $mpdf->SetFooter('Generado el ' . now()->format('Y-m-d H:i:s'));
        $mpdf->WriteHTML($html);

        // Descargar el PDF
        return $mpdf->Output('reporte_estado_resultado.pdf', 'I'); // 'I' para mostrar en el navegador
    }
}
