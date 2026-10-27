<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccountingSetting;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeAccountingSettings
{
    public const TYPES = ['fee_receivable', 'fee_income', 'fee_bank', 'family', 'cash_desk'];

    public function save(User $actor, array $input): FeeAccountingSetting
    {
        app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION);
        $data = Validator::make($input, self::rules())->validate();
        foreach (self::TYPES as $type) {
            foreach (['xero', 'quickbooks'] as $layout) {
                $value = trim($data['mappings'][$type][$layout] ?? '');
                abort_if(preg_match('/\A[\s\x{FEFF}]*[=+@\-]/u', $value) === 1, 422, 'Accounting codes and account names must not begin with spreadsheet formula characters.');
                $data['mappings'][$type][$layout] = $value;
            }
        }
        $data['xero_tax_label'] = trim($data['xero_tax_label'] ?? '');
        $receipt = app(BillingOperations::class)->once($actor, FeePayments::PERMISSION, 'accounting_settings', $data, function () use ($actor, $data): array {
            $settings = FeeAccountingSetting::lockForUpdate()->first() ?? new FeeAccountingSetting;
            abort_unless((int) ($settings->revision ?? 0) === (int) $data['revision'], 409, 'The accounting mapping changed. Review it before saving.');
            $settings->fill(['mappings' => $data['mappings'], 'xero_currency' => $data['xero_currency'] ?? null, 'quickbooks_currency' => $data['quickbooks_currency'] ?? null, 'xero_date_format' => $data['xero_date_format'], 'quickbooks_date_format' => $data['quickbooks_date_format'],
                'xero_tax_label' => $data['xero_tax_label'], 'revision' => (int) ($settings->revision ?? 0) + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.accounting_mapping_saved', $settings, [], ['revision' => $settings->revision]);

            return ['id' => (int) $settings->id];
        });

        return FeeAccountingSetting::findOrFail($receipt['id']);
    }

    public static function labels(): array
    {
        return ['fee_receivable' => 'Fee receivables', 'fee_income' => 'Fee income', 'fee_bank' => 'Fee bank', 'family' => 'Held family credit', 'cash_desk' => 'Fee cash desk'];
    }

    public static function rules(): array
    {
        $rules = ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'xero_currency' => ['nullable', 'regex:/\A[A-Z]{3}\z/D'], 'quickbooks_currency' => ['nullable', 'regex:/\A[A-Z]{3}\z/D'],
            'xero_date_format' => ['required', 'in:d/m/Y,m/d/Y,Y-m-d'], 'quickbooks_date_format' => ['required', 'in:d/m/Y,m/d/Y,Y-m-d'],
            'xero_tax_label' => ['nullable', 'string', 'max:80', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'mappings' => ['required', 'array:'.implode(',', self::TYPES)]];
        foreach (self::TYPES as $type) {
            $rules['mappings.'.$type] = ['required', 'array:xero,quickbooks'];
            foreach (['xero', 'quickbooks'] as $layout) {
                $rules['mappings.'.$type.'.'.$layout] = ['nullable', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/u'];
            }
        }

        return $rules;
    }
}
