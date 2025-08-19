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
        $date_filter_type = $request->get('date_filter_type');
        $month = $request->get('month');
        $day = $request->get('day');
        $date_range = $request->get('date_range');
        if (is_string($date_range)) {
            $decoded = json_decode($date_range, true);
            if (is_array($decoded)) {
                $date_range = $decoded;
            }
        }
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
                ],
                'totals' => [
                    'total_sum' => 0,
                    'commission_sum' => 0,
                ],
            ]);
        }

        $seller = Seller::find($seller_id); // Trae los datos del vendedor

        $perPage = (int) ($request->per_page ?? 20);
        $page = (int) ($request->page ?? 1);
        $all = collect();

        $filterByDate = function($query) use ($date_filter_type, $month, $day, $date_range) {
            if ($date_filter_type === 'month' && $month) {
                $query->whereRaw("DATE_FORMAT(date_of_issue, '%Y-%m') = ?", [$month]);
            } elseif ($date_filter_type === 'day' && $day) {
                $query->whereDate('date_of_issue', $day);
            } elseif ($date_filter_type === 'range' && is_array($date_range) && count($date_range) === 2) {
                $query->whereBetween('date_of_issue', [$date_range[0], $date_range[1]]);
            }
        };

        // Documentos electrónicos
        if (!$document_type_id || $document_type_id === 'document') {
            $documents = Document::with('items.relation_item')
                ->where('seller_id', $seller_id)
                ->when($date_filter_type, $filterByDate)
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
                ->when($date_filter_type, $filterByDate)
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
                ->when($date_filter_type, $filterByDate)
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
                return \Carbon\Carbon::parse($item[$sort_by]);
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

        $total_sum = $all->sum('total');
        $commission_sum = $all->sum('commission');

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page
            ],
            'progress' => $progress,
            'totals' => [
                'total_sum' => $total_sum,
                'commission_sum' => $commission_sum,
            ],
        ]);
    }

    public function exportPdf(Request $request)
    {
        $seller_id = $request->seller_id;
        $seller = Seller::find($seller_id);

        $request->merge(['per_page' => 10000, 'page' => 1]);

        $response = $this->records($request);
        $records = $response->getData(true)['data'] ?? [];
        $total_sum = collect($records)->sum('total');
        $commission_sum = collect($records)->sum('commission');

        $pdf = PDF::loadView('report::sellers.report_pdf', [
            'records' => $records,
            'seller' => $seller,
            'progress' => [
                'total' => count($records),
                'goal' => $seller ? (float) $seller->monthly_goal : 0,
            ],
            'totals' => [
                'total_sum' => $total_sum,
                'commission_sum' => $commission_sum,
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