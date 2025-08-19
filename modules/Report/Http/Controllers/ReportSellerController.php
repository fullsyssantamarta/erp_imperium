<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\Seller;
use App\Models\Tenant\Document;
use App\Models\Tenant\DocumentPos;
use Carbon\Carbon;
use Modules\Factcolombia1\Models\TenantService\TypeDocument;
use Modules\Sale\Models\Remission;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Facades\Excel;

class ReportSellerController extends Controller
{
    public function index()
    {
        return view('report::sellers.index');
    }

    // Endpoint para el select de vendedores activos (con búsqueda y paginación)
    public function getSellers(Request $request)
    {
        $query = Seller::where('status', 1);

        if ($request->has('search') && $request->search) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        $sellers = $query->orderBy('full_name')
            ->paginate(20);

        return response()->json($sellers);
    }

    // Endpoint para tipos de documento (puedes ajustar según tu modelo)
    public function getDocumentTypes()
    {
        // Solo los 3 tipos que tienen seller_id
        return response()->json([
            ['id' => 'document', 'description' => 'Documento electrónico'],
            ['id' => 'document_pos', 'description' => 'Documento POS'],
            ['id' => 'remission', 'description' => 'Remisión'],
        ]);
    }

    // Endpoint para traer los registros asociados al seller
    public function records(Request $request)
    {
        $seller_id = $request->seller_id;
        $document_type_id = $request->document_type_id;
        $month = $request->month;

        $sort_by = $request->get('sort_by', 'date_of_issue');
        $sort_order = $request->get('sort_order', 'desc');

        if (!$seller_id) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'per_page' => 20,
                    'current_page' => 1
                ],
                'progress' => [
                    'total' => 0,
                    'goal' => 0,
                ]
            ]);
        }

        $seller = Seller::find($seller_id); // Trae los datos del vendedor

        $perPage = (int) ($request->per_page ?? 20);
        $page = (int) ($request->page ?? 1);
        $all = collect();

        $filterByMonth = function($query) use ($month) {
            if ($month) {
                $query->whereRaw("DATE_FORMAT(date_of_issue, '%Y-%m') = ?", [$month]);
            }
        };

        // Documentos electrónicos
        if (!$document_type_id || $document_type_id === 'document') {
            $documents = Document::with('items.relation_item')
                ->where('seller_id', $seller_id)
                ->when($month, $filterByMonth)
                ->orderBy('date_of_issue', 'desc')
                ->get()
                ->map(function($doc) use ($seller) {
                    $commission = null;
                    if ($seller && $seller->commission_percentage && $seller->commission_type) {
                        // Puedes personalizar el cálculo según el tipo
                        $base = 0;
                        if ($seller->commission_type === 'total') {
                            $base = $doc->total;
                        } elseif ($seller->commission_type === 'utilidad') {
                            $base = $doc->items->sum(function($item) {
                                $sale_unit_price = $item->relation_item->sale_unit_price ?? 0;
                                $purchase_unit_price = $item->relation_item->purchase_unit_price ?? 0;
                                $quantity = $item->quantity ?? 1;
                                // dd($sale_unit_price, $purchase_unit_price, $quantity);
                                return ($sale_unit_price - $purchase_unit_price) * $quantity;
                            });
                        } elseif ($seller->commission_type === 'producto') {
                            $base = $doc->items->sum(function($item) {
                                return $item->total ?? 0;
                            });
                        }
                        $commission = round($base * ($seller->commission_percentage / 100), 2);
                    }
                    return [
                        'id' => $doc->id,
                        'date_of_issue' => $doc->date_of_issue->format('Y-m-d'),
                        'number_full' => $doc->number_full,
                        'type' => 'Documento electrónico',
                        'customer_name' => $doc->customer->name ?? '',
                        'total' => $doc->total,
                        'commission' => $commission,
                    ];
                });
            $all = $all->merge($documents);
        }

        // Documentos POS
        if (!$document_type_id || $document_type_id === 'document_pos') {
            $documents_pos = DocumentPos::with('items.relation_item')
                ->where('seller_id', $seller_id)
                ->when($month, $filterByMonth)
                ->orderBy('date_of_issue', 'desc')
                ->get()
                ->map(function($doc) use ($seller) {
                    $commission = null;
                    if ($seller && $seller->commission_percentage && $seller->commission_type) {
                        $base = 0;
                        if ($seller->commission_type === 'total') {
                            $base = $doc->total;
                        } elseif ($seller->commission_type === 'utilidad') {
                            $base = $doc->items->sum(function($item) {
                                $sale_unit_price = $item->relation_item->sale_unit_price ?? 0;
                                $purchase_unit_price = $item->relation_item->purchase_unit_price ?? 0;
                                $quantity = $item->quantity ?? 1;
                                return ($sale_unit_price - $purchase_unit_price) * $quantity;
                            });
                        } elseif ($seller->commission_type === 'producto') {
                            $base = $doc->items->sum(function($item) {
                                return $item->total ?? 0;
                            });
                        }
                        $commission = round($base * ($seller->commission_percentage / 100), 2);
                    }
                    return [
                        'id' => $doc->id,
                        'date_of_issue' => $doc->date_of_issue->format('Y-m-d'),
                        'number_full' => $doc->number_full,
                        'type' => 'Documento POS',
                        'customer_name' => $doc->customer->name ?? '',
                        'total' => $doc->total,
                        'commission' => $commission,
                    ];
                });
            $all = $all->merge($documents_pos);
        }

        // Remisiones
        if (!$document_type_id || $document_type_id === 'remission') {
            $remissions = Remission::with('items.relation_item')
                ->where('seller_id', $seller_id)
                ->when($month, $filterByMonth)
                ->orderBy('date_of_issue', 'desc')
                ->get()
                ->map(function($doc) use ($seller) {
                    $commission = null;
                    if ($seller && $seller->commission_percentage && $seller->commission_type) {
                        $base = 0;
                        if ($seller->commission_type === 'total') {
                            $base = $doc->total;
                        } elseif ($seller->commission_type === 'utilidad') {
                            $base = $doc->items->sum(function($item) {
                                $sale_unit_price = $item->relation_item->sale_unit_price ?? 0;
                                $purchase_unit_price = $item->relation_item->purchase_unit_price ?? 0;
                                $quantity = $item->quantity ?? 1;
                                return ($sale_unit_price - $purchase_unit_price) * $quantity;
                            });
                        } elseif ($seller->commission_type === 'producto') {
                            $base = $doc->items->sum(function($item) {
                                return $item->total ?? 0;
                            });
                        }
                        $commission = round($base * ($seller->commission_percentage / 100), 2);
                    }
                    return [
                        'id' => $doc->id,
                        'date_of_issue' => $doc->date_of_issue->format('Y-m-d'),
                        'number_full' => $doc->number_full,
                        'type' => 'Remisión',
                        'customer_name' => $doc->customer->name ?? '',
                        'total' => $doc->total,
                        'commission' => $commission,
                    ];
                });
            $all = $all->merge($remissions);
        }

        $all = $all->sortBy(function($item) use ($sort_by) {
            if ($sort_by === 'date_of_issue') {
                return Carbon::parse($item[$sort_by]);
            }
            return $item[$sort_by];
        }, SORT_REGULAR, $sort_order === 'desc')->values();
        $total = $all->count();
        $data = $all->slice(($page - 1) * $perPage, $perPage)->values();

        $monthly_goal = $seller ? (float) $seller->monthly_goal : 0;
        $progress = [
            'total' => $total,
            'goal' => $monthly_goal,
        ];

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page
            ],
            'progress' => $progress,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $seller_id = $request->seller_id;
        $seller = Seller::find($seller_id);

        $request->merge(['per_page' => 10000, 'page' => 1]);

        $response = $this->records($request);
        $records = $response->getData(true)['data'] ?? [];

        $pdf = PDF::loadView('report::sellers.report_pdf', [
            'records' => $records,
            'seller' => $seller,
            'progress' => [
                'total' => count($records),
                'goal' => $seller ? (float) $seller->monthly_goal : 0,
            ],
        ])->setPaper('a4', 'landscape');

        $filename = 'Reporte_Vendedor_' . ($seller ? $seller->full_name : 'todos') . '_' . date('YmdHis') . '.pdf';
       
        return $pdf->stream($filename);
    }

    public function exportExcel(Request $request)
    {
        $seller_id = $request->seller_id;
        $document_type_id = $request->document_type_id;

        // Convertir string "null" a null real (por si acaso)
        if ($document_type_id === 'null') {
            $request->merge(['document_type_id' => null]);
        }

        $seller = Seller::find($seller_id);

        // Forzar sin paginación
        $request->merge(['per_page' => 10000, 'page' => 1]);

        $response = $this->records($request);
        $records = $response->getData(true)['data'] ?? [];

        $filename = 'Reporte_Vendedor_' . ($seller ? $seller->full_name : 'todos') . '_' . date('YmdHis') . '.xlsx';

        return Excel::download(new class($records, $seller) implements \Maatwebsite\Excel\Concerns\FromView {
            private $records, $seller;
            public function __construct($records, $seller) {
                $this->records = $records;
                $this->seller = $seller;
            }
            public function view(): \Illuminate\Contracts\View\View {
                return View::make('report::sellers.report_excel', [
                    'records' => $this->records,
                    'seller' => $this->seller,
                    'progress' => [
                        'total' => count($this->records),
                        'goal' => $this->seller ? (float) $this->seller->monthly_goal : 0,
                    ],
                ]);
            }
        }, $filename);
    }
}