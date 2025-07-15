<?php

namespace Modules\Accounting\Helpers;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\DailyAccountBalance;
use Carbon\Carbon;

class AccountBalanceHelper
{
    /**
     * Get the balance up to and including the given date.
     */
    public static function getBalanceUpTo(int $accountId, string $date)
    {
        $date = Carbon::parse($date)->format('Y-m-d');
        return DailyAccountBalance::where('account_id', $accountId)
            ->where('date', '<=', $date)
            ->orderByDesc('date')
            ->value('balance') ?? 0;
    }

    /**
     * Insert or update the daily balance for a given date.
     *
     * @param int $accountId // Chart of account
     * @param Carbon|string $date
     * @param float $debit // Debit
     * @param float $credit // Credit
     */
    public static function applyMovementToBalance(int $accountId, string $date, float $debit = 0, float $credit = 0): float
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        // ¿Ya hay un balance ese día?
        $balanceRecord = DailyAccountBalance::where('account_id', $accountId)
            ->where('date', $date)
            ->first();

        $net = $debit - $credit;

        if ($balanceRecord) {
            // Si ya existe, sumamos al balance actual
            $balanceRecord->balance += $net;
            $balanceRecord->save();
            return $balanceRecord->balance;
        }

        // Si no existe, obtenemos el último saldo anterior
        $previousBalance = self::getBalanceUpTo($accountId, $date);
        $newBalance = $previousBalance + $net;

        // Creamos el nuevo registro
        DailyAccountBalance::create([
            'account_id' => $accountId,
            'date' => $date,
            'balance' => $newBalance,
        ]);

        return $newBalance;
    }
}