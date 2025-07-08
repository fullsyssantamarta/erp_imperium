<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument;
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
        } elseif ($format === 'excel') {
            return $this->exportExcel($request);
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

    private function exportExcel(Request $request)
    {
        // Reutilizar la lógica de records para obtener los datos
        $data = $this->records($request)->getData(true);
        $company = Company::first();
        $document_type = TypeIdentityDocument::find($company->type_identity_document_id);

        // Crear el archivo Excel
        $filename = 'reporte_situacion_financiera.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $company->name);
        $sheet->setCellValue('A2', $document_type->name);
        $sheet->setCellValue('B2', $company->identification_number);
        $sheet->setCellValue('A3', 'DIRECCIÓN');
        $sheet->setCellValue('B3', $company->address);

        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $sheet->setCellValue('A5', 'Fechas');
        $sheet->setCellValue('B5', ' del ' . ($dateStart ?? '-') . ' al ' . ($dateEnd ?? '-'));

        // Configurar encabezados
        $sheet->setCellValue('A7', 'Código');
        $sheet->setCellValue('B7', 'Nombre');
        $sheet->setCellValue('C7', 'Tipo');
        $sheet->setCellValue('D7', 'Saldo');

        // Agregar datos de Ingresos
        $row = 8;
        $sheet->setCellValue('A' . $row, 'Ingresos');
        foreach ($data['revenues'] as $asset) {
            $row++;
            $sheet->setCellValue('A' . $row, $asset['code']);
            $sheet->setCellValue('B' . $row, $asset['name']);
            $sheet->setCellValue('C' . $row, $asset['type']);
            $sheet->setCellValue('D' . $row, $asset['saldo']);
        }
        $row++;
        $sheet->setCellValue('C' . $row, 'Total Ingresos');
        $sheet->setCellValue('D' . $row, $data['totals']['revenue']);

        // Agregar datos de Pasivos
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Gastos');
        foreach ($data['expenses'] as $liability) {
            $row++;
            $sheet->setCellValue('A' . $row, $liability['code']);
            $sheet->setCellValue('B' . $row, $liability['name']);
            $sheet->setCellValue('C' . $row, $liability['type']);
            $sheet->setCellValue('D' . $row, $liability['saldo']);
        }
        $row++;
        $sheet->setCellValue('C' . $row, 'Total Gastos');
        $sheet->setCellValue('D' . $row, $data['totals']['expense']);

        // Agregar datos de Patrimonio
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Costos');
        foreach ($data['costs'] as $equity) {
            $row++;
            $sheet->setCellValue('A' . $row, $equity['code']);
            $sheet->setCellValue('B' . $row, $equity['name']);
            $sheet->setCellValue('C' . $row, $equity['type']);
            $sheet->setCellValue('D' . $row, $equity['saldo']);
        }
        $row++;
        $sheet->setCellValue('C' . $row, 'Total Costos');
        $sheet->setCellValue('D' . $row, $data['totals']['cost']);

        $row++;
        $sheet->setCellValue('A' . $row, 'Utilidad Bruta');
        $sheet->setCellValue('B' . $row, $data['gross_profit']);
        $row++;
        $sheet->setCellValue('A' . $row, 'Utilidad Operativa');
        $sheet->setCellValue('B' . $row, $data['operating_profit']);
        $row++;
        $sheet->setCellValue('A' . $row, 'Resultado Neto');
        $sheet->setCellValue('B' . $row, $data['net_profit']);

        // Descargar el archivo Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
