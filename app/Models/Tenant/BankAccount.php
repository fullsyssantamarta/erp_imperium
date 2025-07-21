<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;

use App\Models\Tenant\Catalogs\CurrencyType;
use Hyn\Tenancy\Traits\UsesTenantConnection;
use Modules\Finance\Models\GlobalPayment;
use Modules\Factcolombia1\Models\Tenant\{
    Currency,
};
use Modules\Accounting\Models\ChartOfAccount;

class BankAccount extends ModelTenant
{
    use UsesTenantConnection;

    public $timestamps = false;

    protected $fillable = [
        'bank_id',
        'description',
        'number',
        'currency_id',
        'cci',
        'status',
        'chart_of_account_id'
    ];

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::addGlobalScope('active', function (Builder $builder) {
    //         $builder->where('status', 1);
    //     });
    // }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function currency() {
        return $this->belongsTo(Currency::class);
    }

    public function getCurrencyTypeIdAttribute()
    {
        return $this->currency->name;
    }

    // public function currency_type()
    // {
    //     return $this->belongsTo(CurrencyType::class, 'currency_type_id');
    // }

    public function global_destination()
    {
        return $this->morphMany(GlobalPayment::class, 'destination')->with(['payment']);
    }

    public function chart_of_account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

}
