<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\Seller;
use App\Http\Requests\Tenant\SellerRequest;
use Modules\Factcolombia1\Models\SystemService\TypeDocumentIdentification;

class SellerController extends Controller
{
    public function index()
    {
        return view('factcolombia1::seller.index');
    }

    public function records(Request $request)
    {
        $query = Seller::with('type_document_identification');

        // Filtros
        if ($request->filled('internal_code')) {
            $query->where('internal_code', 'like', '%' . $request->internal_code . '%');
        }
        if ($request->filled('document_number')) {
            $query->where('document_number', 'like', '%' . $request->document_number . '%');
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }
        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', '%' . $request->full_name . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('commission_type')) {
            $query->where('commission_type', $request->commission_type);
        }

        // Paginación
        $sellers = $query->paginate(10);

        $records = $sellers->getCollection()->map(function($seller, $key) {
            return [
                'id' => $seller->id,
                'internal_code' => $seller->internal_code,
                'full_name' => $seller->full_name,
                'type_document_identification_name' => optional($seller->type_document_identification)->name,
                'document_number' => $seller->document_number,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'status' => $seller->status,
                'commission_type' => $seller->commission_type,
            ];
        });

        return response()->json([
            'data' => $records,
            'pagination' => [
                'total' => $sellers->total(),
                'per_page' => $sellers->perPage(),
                'current_page' => $sellers->currentPage(),
                'last_page' => $sellers->lastPage(),
            ]
        ]);
    }

    // Métodos para crear, editar y eliminar 
    public function store(SellerRequest $request)
    {
        $seller = Seller::create($request->all());
        return response()->json(['success' => true, 'id' => $seller->id]);
    }

    public function edit($id)
    {
        $seller = Seller::findOrFail($id);
        return response()->json(['data' => $seller]);
    }

    public function update(SellerRequest $request, $id)
    {
        $seller = Seller::findOrFail($id);
        $seller->update($request->all());
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $seller = Seller::findOrFail($id);
        $seller->delete();
        return response()->json([
            'success' => true,
            'message' => "Se eliminó con éxito el vendedor {$seller->full_name}."
        ]);
    }
    
    public function typeDocuments()
    {
        $types = TypeDocumentIdentification::all();
        return response()->json(['data' => $types]);
    }
    
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Activo,Inactivo'
        ]);
        $seller = Seller::findOrFail($id);
        $seller->status = $request->status;
        $seller->save();
        return response()->json(['success' => true]);
    }

    public function activeSellers(Request $request)
    {
        $query = Seller::where('status', 'Activo');
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('full_name', 'like', "%{$search}%");
        }
        $sellers = $query->get(['id', 'full_name']);
        return response()->json(['data' => $sellers]);
    }
}