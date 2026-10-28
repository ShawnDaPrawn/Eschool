<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeItem;
use App\Models\Money\FeeTaxSetting;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class FeeTaxes
{
    /** Current configuration is used only for a new reviewed charge, never a credit. */
    public function snapshot(FeeItem $item): array
    {
        return $this->forPrice($item->amount_cents, $item->tax_treatment, $item->vat_rate_bp);
    }

    /** A reviewed charge can use explicit policy prices without creating a catalogue item. */
    public function forPrice(int $cents, string $treatment, int $rateBp): array
    {
        try {
            $amounts = FeeTaxAmounts::price($cents, $treatment, $rateBp);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['tax_treatment' => $e->getMessage()]);
        }
        $details = null;
        if (in_array($treatment, ['vat_zero', 'vat_inclusive', 'vat_exclusive'], true)) {
            $setting = FeeTaxSetting::lockForUpdate()->first();
            abort_unless($setting !== null && $setting->enabled, 422, 'Configure and enable registered school VAT details before charging VAT.');
            $details = $setting->only(['country_code', 'tax_registration', 'seller_name', 'seller_address', 'revision']);
        }

        return $amounts + ['tax_details' => $details];
    }

    /** Attribute each holder's original VAT, then preserve it across learner lines. */
    public static function shares(array $shares, array $holderTotals, int $vat): array
    {
        $grossByHolder = array_column($holderTotals, 'amount_cents', 'user_id');
        if ($vat === 0) {
            return ['lines' => $shares, 'holder_vat' => array_fill_keys(array_keys($grossByHolder), 0)];
        }
        $holderVat = FeeTaxAmounts::split($vat, $grossByHolder);
        $byLearner = [];
        foreach ($grossByHolder as $holder => $gross) {
            $weights = [];
            foreach ($shares as $learner => $rows) {
                $weights[$learner] = 0;
                foreach ($rows as $row) {
                    if ($row['user_id'] === $holder) {
                        $weights[$learner] = $row['amount_cents'];
                        break;
                    }
                }
            }
            $slices = $gross === 0 ? array_fill_keys(array_keys($weights), 0) : FeeTaxAmounts::split($holderVat[$holder], $weights);
            foreach ($slices as $learner => $cents) {
                $byLearner[$learner][$holder] = $cents;
            }
        }
        foreach ($shares as $learner => &$rows) {
            foreach ($rows as &$row) {
                $row['vat_cents'] = $byLearner[$learner][$row['user_id']];
            }unset($row);
        }unset($rows);

        return ['lines' => $shares, 'holder_vat' => $holderVat];
    }
}
