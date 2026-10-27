<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeTaxSetting;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeTaxSettings
{
    public function save(User $actor, array $input): FeeTaxSetting
    {
        app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION);
        foreach (['country_code', 'tax_registration', 'seller_name', 'seller_address'] as $key) {
            if (is_string($input[$key] ?? null)) {
                $input[$key] = trim($input[$key]);
            }
        }
        $data = Validator::make($input, self::rules())->validate();
        $data['enabled'] = (bool) $data['enabled'];
        $data['revision'] = (int) $data['revision'];
        $receipt = app(BillingOperations::class)->once($actor, FeeItems::PERMISSION, 'fee_tax_settings', $data, function () use ($actor, $data): array {
            $setting = FeeTaxSetting::lockForUpdate()->first() ?? new FeeTaxSetting;
            abort_unless(($setting->revision ?? 0) === $data['revision'], 409, 'The school VAT details changed. Review their current revision.');
            $setting->fill(['enabled' => $data['enabled'], 'country_code' => $data['country_code'] ?? null, 'tax_registration' => $data['tax_registration'] ?? null, 'seller_name' => $data['seller_name'] ?? null, 'seller_address' => $data['seller_address'] ?? null, 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.fee_tax_settings_saved', $setting, [], ['enabled' => $setting->enabled, 'revision' => $setting->revision]);

            return ['id' => (int) $setting->id];
        });

        return FeeTaxSetting::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'enabled' => ['required', 'boolean'],
            'country_code' => ['nullable', 'required_if:enabled,1', 'string', 'regex:/\A[A-Z]{2}\z/D'],
            'tax_registration' => ['nullable', 'required_if:enabled,1', 'string', 'max:40', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'seller_name' => ['nullable', 'required_if:enabled,1', 'string', 'max:160', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'seller_address' => ['nullable', 'required_if:enabled,1', 'string', 'max:1000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u']];
    }
}
