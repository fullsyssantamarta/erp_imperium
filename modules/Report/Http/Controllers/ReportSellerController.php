<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\Seller;
use App\Models\Tenant\Document;
use App\Models\Tenant\DocumentPos;
use Modules\Factcolombia1\Models\TenantService\TypeDocument;
use Modules\Sale\Models\Remission;

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

        if (!$seller_id) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'per_page' => 20,
                    'current_page' => 1
                ]
            ]);
        }

        $seller = Seller::find($seller_id); // Trae los datos del vendedor

        $perPage = (int) ($request->per_page ?? 20);
        $page = (int) ($request->page ?? 1);
        $all = collect();

        // Documentos electrónicos
        if (!$document_type_id || $document_type_id === 'document') {
            $documents = Document::with('items.relation_item')
                ->where('seller_id', $seller_id)
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

        $all = $all->sortByDesc('date_of_issue')->values();
        $total = $all->count();
        $data = $all->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page
            ]
        ]);
    }
}