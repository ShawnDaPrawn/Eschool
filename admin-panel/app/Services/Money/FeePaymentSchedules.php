<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeePlan;
use App\Models\SessionYear;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Relative calendar schedules; rounding preserves each holder's exact annual cents. */
final class FeePaymentSchedules
{
    public static function defaults(): array
    {
        return [['key' => 'annual', 'label' => 'Annual', 'installments' => []]];
    }

    public function normalize(?array $input, SessionYear $year): array
    {
        $data = Validator::make(['options' => $input ?? self::defaults()], [
            'options' => ['required', 'array', 'min:1', 'max:6'],
            'options.*' => ['required', 'array:key,label,installments'],
            'options.*.key' => ['required', 'string', 'regex:/\A[a-z][a-z0-9_-]{0,31}\z/D', 'distinct'],
            'options.*.label' => ['required', 'string', 'max:100'],
            'options.*.installments' => ['present', 'array', 'max:24'],
            'options.*.installments.*' => ['required', 'array:month,day,weight_bp'],
            'options.*.installments.*.month' => ['required', 'integer', 'between:1,12'],
            'options.*.installments.*.day' => ['required', 'integer', 'between:1,31'],
            'options.*.installments.*.weight_bp' => ['required', 'integer', 'between:1,10000'],
        ])->validate()['options'];
        foreach ($data as &$option) {
            $total = 0;
            $dates = [];
            foreach ($option['installments'] as &$part) {
                foreach (['month', 'day', 'weight_bp'] as $key) {
                    $part[$key] = (int) $part[$key];
                }
                $date = $this->date($year, $part['month'], $part['day']);
                if (in_array($date, $dates, true)) {
                    throw ValidationException::withMessages(['payment_options' => 'Each option needs distinct installment dates.']);
                }
                $dates[] = $date;
                $total += $part['weight_bp'];
            }unset($part);
            if ($option['installments'] !== [] && $total !== 10000) {
                throw ValidationException::withMessages(['payment_options' => 'Installment percentages must total exactly 100%.']);
            }
            usort($option['installments'], fn ($a, $b) => $this->date($year, $a['month'], $a['day']) <=> $this->date($year, $b['month'], $b['day']));
        }unset($option);

        return array_values($data);
    }

    public function date(SessionYear $year, int $month, int $day): string
    {
        $start = substr((string) $year->start_date, 0, 10);
        $end = substr((string) $year->end_date, 0, 10);
        $y = (int) substr($start, 0, 4);
        $date = sprintf('%04d-%02d-%02d', $y, $month, $day);
        if ($date < $start) {
            $y++;
            $date = sprintf('%04d-%02d-%02d', $y, $month, $day);
        }
        if (! checkdate($month, $day, $y) || $date < $start || $date > $end) {
            throw ValidationException::withMessages(['payment_options' => 'An installment date is invalid or outside the selected school year.']);
        }

        return $date;
    }

    public function lines(FeePlan $plan, SessionYear $year, string $fallback, array $holders, ?string $choice = null): array
    {
        $options = $this->normalize($plan->payment_options, $year);
        $key = $choice ?? $plan->default_payment_option ?? $options[0]['key'];
        $option = collect($options)->firstWhere('key', $key);
        if ($option === null) {
            throw ValidationException::withMessages(['payment_option' => 'The selected payment option is unavailable for this plan.']);
        }
        $parts = $option['installments'];
        if ($parts === []) {
            $parts = [['due_on' => $fallback, 'weight_bp' => 10000]];
        } else {
            $parts = array_map(fn ($p) => ['due_on' => $this->date($year, $p['month'], $p['day']), 'weight_bp' => $p['weight_bp']], $parts);
        }
        $lines = [];
        foreach ($parts as $i => $part) {
            $lines[$i] = ['installment_number' => $i + 1, 'due_on' => $part['due_on'], 'amount_cents' => 0, 'holder_shares' => [], 'payment_option' => $key, 'payment_option_label' => $option['label']];
        }
        foreach (BillingPreview::shares($plan->amount_cents, $holders) as $holder) {
            $split = self::split($holder['amount_cents'], array_column($parts, 'weight_bp'));
            foreach ($split as $i => $cents) {
                $lines[$i]['amount_cents'] += $cents;
                $lines[$i]['holder_shares'][] = array_replace($holder, ['amount_cents' => $cents]);
            }
        }

        return array_values(array_filter($lines, fn ($l) => $l['amount_cents'] > 0));
    }

    /** Integer largest remainders, chronological ties. */
    public static function split(int $cents, array $weights): array
    {
        if ($cents < 0 || $cents > FeePlans::MAX_CENTS || $weights === [] || count($weights) > 24 || array_sum($weights) !== 10000) {
            throw new \InvalidArgumentException('Use bounded cents and weights totaling 10000.');
        }
        $out = [];
        $r = [];
        foreach ($weights as $i => $weight) {
            if (! is_int($weight) || $weight < 1 || $weight > 10000) {
                throw new \InvalidArgumentException('Positive integer weights required.');
            }$weighted = $cents * $weight;
            $out[$i] = intdiv($weighted, 10000);
            $r[$i] = $weighted % 10000;
        }
        $indices = array_keys($r);
        usort($indices, fn ($a, $b) => ($r[$b] <=> $r[$a]) ?: ($a <=> $b));
        $left = $cents - array_sum($out);
        for ($n = 0; $n < $left; $n++) {
            $out[$indices[$n]]++;
        }

        return $out;
    }
}
