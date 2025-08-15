<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Modules\Factcolombia1\Models\SystemService\TypeDocumentIdentification;
use Hyn\Tenancy\Traits\UsesTenantConnection;
class Seller extends Model
{
    use UsesTenantConnection;
    
    protected $table = 'sellers';

    protected $fillable = [
        'internal_code',
        'full_name',
        'type_document_identification_id',
        'document_number',
        'birth_date',
        'address',
        'phone',
        'email',
        'start_date',
        'status',
        'role',
        'area',
        'shift',
        'monthly_goal',
        'commission_percentage',
        'commission_type',
    ];

    public function type_document_identification()
    {
        return $this->belongsTo(TypeDocumentIdentification::class, 'type_document_identification_id');
    }
}
