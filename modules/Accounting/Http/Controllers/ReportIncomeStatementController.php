<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ChartOfAccount;

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

        // Ahora agrupamos por tipo:
        $totalRevenue = $accounts->where('type', 'Revenue')->sum('saldo');
        $totalCost    = $accounts->where('type', 'Cost')->sum('saldo');
        $totalExpense = $accounts->where('type', 'Expense')->sum('saldo');

        // ✅ Utilidades:
        $grossProfit     = $totalRevenue - $totalCost;         // Utilidad Bruta
        $operatingProfit = $grossProfit - $totalExpense;       // Utilidad Operativa
        $netProfit       = $operatingProfit;                   // Por ahora igual a operativa

        return response()->json([
            'accounts' => $accounts,
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
}
