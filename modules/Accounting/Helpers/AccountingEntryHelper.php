<?php

namespace Modules\Accounting\Helpers;

use Modules\Accounting\Helpers\AccountBalanceHelper;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryDetail;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Factcolombia1\Models\Tenant\Tax;
use Carbon\Carbon;

class AccountingEntryHelper
{

    public static function registerEntry(array $document): void
    {
        $entry = JournalEntry::createWithNumber([
            'date' => date('Y-m-d'),
            'journal_prefix_id' => $document['prefix_id'],
            'description' => $document['description'],
            'document_id' => $document['document_id'] ?? null,
            'purchase_id' => $document['purchase_id'] ?? null,
            'status' => 'posted',
        ]);

        foreach ($document['movements'] as $movement) {
            $entry->details()->create([
                'chart_of_account_id' => $movement['account_id'],
                'debit' => $movement['debit'],
                'credit' => $movement['credit'],
            ]);

            if ($movement['affects_balance'] ?? false) {
                AccountBalanceHelper::applyMovementToBalance(
                    $movement['account_id'],
                    Carbon::now(),
                    $movement['debit'],
                    $movement['credit']
                );
            }
        }

        // Procesar impuestos
        if (!empty($document['taxes'])) {
            self::handleTaxes($entry, $document['taxes'], $document['tax_config']);
        }
    }

    private static function handleTaxes($entry, array $taxes, array $taxConfig): void
    {
        foreach ($taxes as $taxData) {
            $tax = is_array($taxData) ? (object)$taxData : $taxData;
            $taxModel = Tax::find($tax->id);
            if (!$taxModel) continue;

            // Impuestos normales
            if (floatval($tax->total) > 0 && isset($taxConfig['tax_field'])) {
                $accountCode = $taxModel->{$taxConfig['tax_field']} ?? null;
                if ($accountCode) {
                    $account = ChartOfAccount::where('code', $accountCode)->first();
                    if ($account) {
                        $entry->details()->create([
                            'chart_of_account_id' => $account->id,
                            'debit' => $taxConfig['tax_debit'] ? $tax->total : 0,
                            'credit' => $taxConfig['tax_credit'] ? $tax->total : 0,
                        ]);
                    }
                }
            }

            // Retención
            if ($tax->is_retention && floatval($tax->retention) > 0 && isset($taxConfig['tax_field'])) {
                $accountCode = $taxModel->{$taxConfig['tax_field']} ?? null;
                if ($accountCode) {
                    $account = ChartOfAccount::where('code', $accountCode)->first();
                    if ($account) {
                        $entry->details()->create([
                            'chart_of_account_id' => $account->id,
                            'debit' => $taxConfig['retention_debit'] ? $tax->retention : 0,
                            'credit' => $taxConfig['retention_credit'] ? $tax->retention : 0,
                        ]);
                    }
                }
            }
        }
    }
}