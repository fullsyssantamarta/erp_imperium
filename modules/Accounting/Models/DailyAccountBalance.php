<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Modules\Accounting\Models\ChartOfAccount;

class DailyAccountBalance extends ModelTenant
{
    protected $fillable = [
        'account_id',
        'date',
        'balance',
    ];

    protected $casts = [
        'date' => 'date',
        'balance' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
