<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryDetail;
use Mpdf\Mpdf;

class ReportAuxiliaryMovementController extends Controller
{
    public function index()
    {
        return view('accounting::reports.auxiliary_movement');
    }

    public function records(Request $request)
    {
        $dateStart = $request->input('date_start', now()->startOfMonth()->toDateString()) . ' 00:00:00';
        $dateEnd = $request->input('date_end', now()->endOfMonth()->toDateString()) . ' 23:59:59';

        $accounts = JournalEntryDetail::whereBetween('created_at', [$dateStart, $dateEnd])
            ->with(['chartOfAccount', 'journalEntry'])
            ->get()
            ->map(function ($detail) {
                $account = $detail->chartOfAccount;
                $entry = $detail->journalEntry;
                // centralizo la obtencion de datos del documento
                $documentInfo = $this->getDocumentInfo($entry);

                return [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'document_info' => $documentInfo,
                    'date' => $entry->date,
                    'debit' => $detail->debit,
                    'credit' => $detail->credit,
                    'description' => $entry->description,
                ];
            })
            ->groupBy('account_code')
                ->map(function ($items, $account_code) {
                $totalDebit = $items->sum('debit');
                $totalCredit = $items->sum('credit');
                $accountName = $items->first()['account_name'] ?? '';

                return [
                    'account_code' => $account_code,
                    'account_name' => $accountName,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'details' => $items->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $accounts,
            'message' => 'Movimientos auxiliares obtenidos correctamente.',
        ]);
    }

    /**
     * Centraliza la lógica para obtener los campos relevantes según el tipo de documento.
     */
    private function getDocumentInfo($entry)
    {
        if ($entry->purchase) {
            $purchase = $entry->purchase;
            return [
                'type' => 'purchase',
                'id' => $purchase->id,
                'number' => $purchase->series . '-' . $purchase->number,
                'third_party_number' => $purchase->supplier->number,
                'third_party_name' => $purchase->supplier->name,
            ];
        }
        if ($entry->document) {
            $document = $entry->document;
            return [
                'type' => 'document',
                'id' => $document->id,
                'number' => $document->prefix . '-' . $document->number,
                'third_party_number' => $document->customer->number,
                'third_party_name' => $document->customer->name,
            ];
        }
        // TO DO - inventory - extra...
        return null;
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
        $html = view('accounting::pdf.auxiliary_movement', [
            'accounts' => $data['data'],
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ])->render();

        // Configurar mPDF
        $mpdf = new Mpdf(['orientation' => 'L']);
        $mpdf->SetHeader('Reporte de Movimientos auxiliares');
        $mpdf->SetFooter('Generado el ' . now()->format('Y-m-d H:i:s'));
        $mpdf->WriteHTML($html);

        // Descargar el PDF
        return $mpdf->Output('reporte_movimientos_auxiliares.pdf', 'I'); // 'I' para mostrar en el navegador
    }

    private function exportExcel(Request $request)
    {
        // Reutilizar la lógica de records para obtener los datos
        $data = $this->records($request)->getData(true);

        // Crear el archivo Excel
        $filename = 'reporte_situacion_financiera.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar encabezados
        $sheet->setCellValue('A1', 'Código');
        $sheet->setCellValue('B1', 'Cuenta');
        $sheet->setCellValue('C1', 'Comprobante');
        $sheet->setCellValue('D1', 'Número de documento');
        $sheet->setCellValue('E1', 'Nombre del tercero');
        $sheet->setCellValue('F1', 'Descripción');
        $sheet->setCellValue('G1', 'Débito');
        $sheet->setCellValue('H1', 'Crédito');


        // Agregar datos de cuentas
        $row = 2;
        foreach ($data['data'] as $group) {
            $row++;
            $sheet->setCellValue('A' . $row, 'Cuenta contable:');
            $sheet->setCellValue('B' . $row, $group['account_code']);
            $sheet->setCellValue('G' . $row, $group['total_debit']);
            $sheet->setCellValue('H' . $row, $group['total_credit']);
            foreach($group['details'] as $detail) {
                $row++;
                $sheet->setCellValue('A' . $row, $detail['account_code']);
                $sheet->setCellValue('B' . $row, $detail['account_name']);
                $sheet->setCellValue('C' . $row, $detail['document_info']['type'] ?? '');
                $sheet->setCellValue('D' . $row, $detail['document_info']['number'] ?? '');
                $sheet->setCellValue('E' . $row, $detail['document_info']['third_party_name'] ?? '');
                $sheet->setCellValue('F' . $row, $detail['description']);
                $sheet->setCellValue('G' . $row, $detail['debit']);
                $sheet->setCellValue('H' . $row, $detail['credit']);
            }
        }
        $row++;

        // Descargar el archivo Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
