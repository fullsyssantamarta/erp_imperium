<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class SellerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'internal_code' => 'required|unique:tenant.sellers,internal_code,' . $this->id,
            'full_name' => 'required|string|max:255',
            'type_document_identification_id' => 'required|integer',
            'document_number' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'status' => 'required|in:Activo,Inactivo',
            'role' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:255',
            'monthly_goal' => 'nullable|numeric',
            'commission_percentage' => 'nullable|numeric',
            'commission_type' => 'nullable|string|max:255',
        ];
    }
}