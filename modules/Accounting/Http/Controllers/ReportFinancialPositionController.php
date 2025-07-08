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
 * Class ReportFinancialPositionController
 * Reporte de Situación Financiera
 */
class ReportFinancialPositionController extends Controller
{
    public function index()
    {
        return view('accounting::reports.financial_position');
    }

    // activos Asset
    // pasivos Liability
    // patrimonio Equity
    public function records(Request $request)
    {
        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;

        $accounts = ChartOfAccount::whereIn('type', ['Asset', 'Liability', 'Equity'])
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

                // Calcular el saldo según la naturaleza de la cuenta
                if ($account->type === 'Asset') {
                    $saldo = $debit - $credit; // Activos: Débito - Crédito
                } elseif ($account->type === 'Liability' || $account->type === 'Equity') {
                    $saldo = $credit - $debit; // Pasivos y Patrimonio: Crédito - Débito
                } else {
                    $saldo = 0; // Por defecto, saldo 0
                }

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'saldo' => $saldo,
                ];
            });

        // Separar las cuentas por tipo
        $assets = $accounts->where('type', 'Asset')->where('saldo', '>', 0);
        $liabilities = $accounts->where('type', 'Liability')->where('saldo', '>', 0);
        $equity = $accounts->where('type', 'Equity')->where('saldo', '>', 0);

        // Calcular los totales por grupo
        $totalAssets = $assets->sum('saldo');
        $totalLiabilities = $liabilities->sum('saldo');
        $totalEquity = $equity->sum('saldo');

        return response()->json([
            'assets' => $assets->values()->all(),
            'liabilities' => $liabilities->values()->all(),
            'equity' => $equity->values()->all(),
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
            ],
        ]);
    }

    public function export(Request $request)
    {
        // Obtener el formato (pdf o excel) del request
        $format = $request->input('format', 'pdf'); // Por defecto, PDF

        // Llamar al método correspondiente según el formato
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
        $html = view('accounting::pdf.financial_position', [
            'assets' => $data['assets'],
            'liabilities' => $data['liabilities'],
            'equity' => $data['equity'],
            'totals' => $data['totals'],
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ])->render();

        // Configurar mPDF
        $mpdf = new Mpdf();
        $mpdf->SetHeader('Reporte de Situación Financiera');
        $mpdf->SetFooter('Generado el ' . now()->format('Y-m-d H:i:s'));
        $mpdf->WriteHTML($html);

        // Descargar el PDF
        return $mpdf->Output('reporte_situacion_financiera.pdf', 'I'); // 'I' para mostrar en el navegador
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

        // Agregar datos de Activos
        $row = 8;
        $sheet->setCellValue('A' . $row, 'Activos');
        foreach ($data['assets'] as $asset) {
            $row++;
            $sheet->setCellValue('A' . $row, $asset['code']);
            $sheet->setCellValue('B' . $row, $asset['name']);
            $sheet->setCellValue('C' . $row, $asset['type']);
            $sheet->setCellValue('D' . $row, $asset['saldo']);
        }
        $row++;
        $sheet->setCellValue('C' . $row, 'Total Activos');
        $sheet->setCellValue('D' . $row, $data['totals']['assets']);

        // Agregar datos de Pasivos
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Pasivos');
        foreach ($data['liabilities'] as $liability) {
            $row++;
            $sheet->setCellValue('A' . $row, $liability['code']);
            $sheet->setCellValue('B' . $row, $liability['name']);
            $sheet->setCellValue('C' . $row, $liability['type']);
            $sheet->setCellValue('D' . $row, $liability['saldo']);
        }
        $row++;
        $sheet->setCellValue('C' . $row, 'Total Pasivos');
        $sheet->setCellValue('D' . $row, $data['totals']['liabilities']);

        // Agregar datos de Patrimonio
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Patrimonio');
        foreach ($data['equity'] as $equity) {
            $row++;
            $sheet->setCellValue('A' . $row, $equity['code']);
            $sheet->setCellValue('B' . $row, $equity['name']);
            $sheet->setCellValue('C' . $row, $equity['type']);
            $sheet->setCellValue('D' . $row, $equity['saldo']);
        }
        $row++;
        $sheet->setCellValue('C' . $row, 'Total Patrimonio');
        $sheet->setCellValue('D' . $row, $data['totals']['equity']);

        // Descargar el archivo Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
