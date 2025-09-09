<?php

namespace Modules\Factcolombia1\Models\System;

use Illuminate\Database\Eloquent\SoftDeletes;
use Hyn\Tenancy\Traits\UsesSystemConnection;
use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Models\Hostname;
use App\Models\System\ClientPayment;
use App\Models\System\Plan;

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
        'plan_id',
        'plan_started_at',
        'plan_expires_at',
        'plan_auto_renew',
        'plan_active',
        'start_billing_cycle'
    ];

    protected $casts = [
        'start_billing_cycle' => 'date',
        'plan_started_at' => 'date',
        'plan_expires_at' => 'date',
        'plan_auto_renew' => 'boolean',
        'plan_active' => 'boolean',
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

    public function payments()
    {
        return $this->hasMany(ClientPayment::class, 'companie_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function isPlanActive()
    {
        return $this->plan_active && $this->plan_expires_at && $this->plan_expires_at->isFuture();
    }
}
