<?php

namespace Modules\Factcolombia1\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Factcolombia1\Traits\Tenant\RequestsTrait;

class AdvancedConfigurationRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'uvt' => 'required|numeric|gte:0',
            'rips_enabled' => 'nullable|boolean',
            'rips_type_document_identification_id' => 'nullable|integer|exists:co_service_type_document_identifications,id',
            'rips_number_identification' => 'nullable|string|max:191',
            'rips_password' => 'nullable|string|max:191',
            'rips_url' => 'nullable|url',
        ];
    }

    
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() 
    {
        return true;
    }

}
