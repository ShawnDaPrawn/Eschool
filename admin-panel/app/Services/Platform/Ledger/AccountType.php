<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

enum AccountType: string
{
    case FeeReceivable = 'fee_receivable';
    case Family = 'family';
    case Wallet = 'wallet';
    case Vendor = 'vendor';
    case Clearing = 'clearing';
    case DueToSchool = 'due_to_school';
    case CashDesk = 'cash_desk';

    public function normalDirection(): string
    {
        return match ($this) {
            self::FeeReceivable, self::CashDesk, self::Clearing => 'debit',
            default => 'credit',
        };
    }

    public function module(): string
    {
        return match ($this) {
            self::Wallet => 'wallet',
            self::Vendor => 'vendors',
            default => 'fees',
        };
    }
}
