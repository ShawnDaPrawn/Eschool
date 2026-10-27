<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class FeeItems
{
    public const PERMISSION = 'fee-items-manage';

    public function save(User $actor, array $input, ?int $id = null): FeeItem
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        if (is_string($input['name'] ?? null)) {
            $input['name'] = trim($input['name']);
        }
        $data = Validator::make($input, self::rules())->validate();
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= FeePlans::MAX_CENTS, 422, 'Use a positive supported fee item amount.');
        unset($data['amount']);
        $data['item_id'] = $id;
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_item_save', $data, function () use ($data, $id): array {
            $item = $id === null ? new FeeItem : FeeItem::lockForUpdate()->findOrFail($id);
            abort_unless((int) ($item->revision ?? 0) === (int) $data['revision'], 409, 'This fee item changed. Review it before saving again.');
            abort_if($item->exists && FeeCharge::where('fee_item_id', $item->id)->exists() && ($item->scope !== $data['scope'] || $item->currency !== $data['currency']), 422, 'An item with charge history keeps its scope and currency.');
            try {
                $rate = array_key_exists('vat_rate', $data) ? FeeTaxAmounts::rate($data['vat_rate']) : ($item->vat_rate_bp ?? 0);
                FeeTaxAmounts::price($data['amount_cents'], $data['tax_treatment'], $rate);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['tax_treatment' => $e->getMessage()]);
            }
            $item->fill(['name' => $data['name'], 'scope' => $data['scope'], 'amount_cents' => $data['amount_cents'], 'currency' => $data['currency'], 'tax_treatment' => $data['tax_treatment'], 'vat_rate_bp' => $rate, 'state' => $data['state'], 'revision' => (int) ($item->revision ?? 0) + 1])->save();
            Audit::record('money.fee_item_saved', $item, [], ['revision' => $item->revision, 'amount_cents' => $item->amount_cents, 'scope' => $item->scope, 'state' => $item->state]);

            return ['id' => (int) $item->id];
        });

        return FeeItem::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'name' => ['required', 'string', 'min:1', 'max:160'], 'scope' => ['required', 'in:learner,family'], 'amount' => ['required', 'string', 'regex:/\A\d{1,9}(?:\.\d{1,2})?\z/D'], 'currency' => ['required', 'string', 'regex:/\A[A-Z]{3}\z/D'], 'tax_treatment' => ['required', 'in:'.implode(',', FeeTaxAmounts::TREATMENTS)], 'vat_rate' => ['sometimes', 'string', 'regex:/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/D'], 'state' => ['required', 'in:draft,active,archived']];
    }
}
