<?php

namespace Modules\Factcolombia1\Models\System;

use Illuminate\Database\Eloquent\SoftDeletes;
use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Models\Hostname;

class Company extends Model
{
    use SoftDeletes, UsesSystemConnection;
    
    protected $table = 'co_companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identification_number', 
        'name', 
        'email', 
        'subdomain', 
        'limit_documents', 
        'hostname_id', 
        'economic_activity_code', 
        'ica_rate',
        'locked',
        'locked_emission',
        'locked_tenant',
        'locked_users',
        'limit_users',
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    public function hostname() {
        return $this->belongsTo(Hostname::class);
    }
}
