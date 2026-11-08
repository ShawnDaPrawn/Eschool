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
    case WalletBank = 'wallet_bank';
    case Family = 'family';
    case Wallet = 'wallet';
    case Vendor = 'vendor';
    case Clearing = 'clearing';
    case DueToSchool = 'due_to_school';
    case CashDesk = 'cash_desk';
    // Alumni giving (Action Plan 5.10): its own bank and campaign income.
    case GivingBank = 'giving_bank';
    case GivingIncome = 'giving_income';

    public function normalDirection(): string
    {
        return match ($this) {
            self::FeeReceivable, self::FeeBank, self::WalletBank, self::CashDesk, self::Clearing, self::GivingBank => 'debit',
            default => 'credit',
        };
    }

    public function module(): string
    {
        return match ($this) {
            self::Wallet, self::WalletBank => 'wallet',
            self::Vendor => 'vendors',
            self::GivingBank, self::GivingIncome => 'giving',
            default => 'fees',
        };
    }

    /** @return list<string> */
    public function allowedModules(): array
    {
        return match ($this) {
            self::CashDesk, self::Clearing, self::DueToSchool => ['fees', 'wallet', 'vendors', 'ticketing'],
            self::FeeIncome, self::FeeVat => ['fees', 'vendors', 'ticketing'],
            default => [$this->module()],
        };
    }
}
