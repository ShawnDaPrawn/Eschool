<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

enum AccountType: string
{
    case FeeReceivable = 'fee_receivable';
    case FeeIncome = 'fee_income';
    case FeeVat = 'fee_vat';
    case FeeRefund = 'fee_refund';
    case FeeBank = 'fee_bank';
    case Family = 'family';
    case Wallet = 'wallet';
    case Vendor = 'vendor';
    case Clearing = 'clearing';
    case DueToSchool = 'due_to_school';
    case CashDesk = 'cash_desk';

    public function normalDirection(): string
    {
        return match ($this) {
            self::FeeReceivable, self::FeeBank, self::CashDesk, self::Clearing => 'debit',
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

    /** @return list<string> */
    public function allowedModules(): array
    {
        return match ($this) {
            self::CashDesk, self::Clearing, self::DueToSchool => ['fees', 'wallet', 'vendors', 'ticketing'],
            default => [$this->module()],
        };
    }
}
