<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
use App\Models\User;
use App\Services\Money\FeeCreditHistory;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Applied learner receipts; excludes held credit and unassigned family charges. */
class LeadershipFeeCollections
{
    private const MAX_CENTS = 9007199254740991;

    public function read(User $actor, int $year, string $start, string $end): array
    {
        $authority = app(LeadershipAuthority::class);
        $authority->principal($actor, $year);
        $gate = app(ModuleGate::class);
        $gate->forget();
        $off = ['enabled' => false, 'events' => [], 'unavailable' => [], 'currency' => null];
        if (! $gate->allows('fees')) {
            return $off;
        }
        $snapshot = DB::transaction(function () use ($year, $start, $end): array {
            $school = app(CurrentSchool::class)->id();
            $rosters = Enrolment::with('homeroom.class')->where('session_year_id', $year)
                ->whereDate('starts_on', '<=', $end)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))
                ->get()->groupBy('learner_id');
            $rows = DB::table('payment_allocations as a')
                ->join('payment_allocation_runs as r', fn ($j) => $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.allocation_run_id'))
                ->join('payments as p', fn ($j) => $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id'))
                ->join('invoice_lines as l', fn ($j) => $j->on('l.school_id', '=', 'a.school_id')->on('l.id', '=', 'a.invoice_line_id'))
                ->join('invoices as i', fn ($j) => $j->on('i.school_id', '=', 'l.school_id')->on('i.id', '=', 'l.invoice_id'))
                ->join('bill_runs as b', fn ($j) => $j->on('b.school_id', '=', 'i.school_id')->on('b.id', '=', 'i.bill_run_id'))
                ->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'r.school_id')->on('e.id', '=', 'r.ledger_entry_id'))
                ->where('a.school_id', $school)->where('l.session_year_id', $year)->whereNotNull('l.learner_id')
                ->whereNotNull('r.sealed_at')->whereNotNull('i.sealed_at')->where('b.state', 'posted')->whereNotNull('e.posted_at')
                ->whereBetween('p.received_on', [$start, $end])
                ->get(['a.id', 'a.amount_cents', 'l.learner_id', 'l.currency as line_currency', 'p.currency', 'p.received_on']);
            $released = FeeCreditHistory::releases()->whereIn('c.allocation_id', $rows->pluck('id')->all())
                ->selectRaw('c.allocation_id, SUM(c.amount_cents) AS cents')->groupBy('c.allocation_id')->pluck('cents', 'allocation_id');
            $currencies = FeePlan::where('session_year_id', $year)->distinct()->pluck('currency');
            $events = [];
            $unavailable = [];
            foreach ($rows as $row) {
                $dated = $rosters->get($row->learner_id, collect())->filter(fn ($e) => $e->starts_on->toDateString() <= $row->received_on && ($e->ends_on === null || $e->ends_on->toDateString() >= $row->received_on));
                $amount = $this->cents($row->amount_cents);
                $credit = $this->cents($released[$row->id] ?? 0);
                if ($dated->count() !== 1 || $amount === null || $credit === null || $credit > $amount || $row->currency !== $row->line_currency) {
                    foreach ($dated as $enrolment) {
                        if ($enrolment->homeroom?->class?->grade_level_id !== null) {
                            $unavailable[(int) $enrolment->homeroom->class->grade_level_id] = true;
                        }
                    }

                    continue;
                }
                $grade = $dated->sole()->homeroom?->class?->grade_level_id;
                if ($grade !== null) {
                    $events[] = ['date' => $row->received_on, 'grade_id' => (int) $grade, 'cents' => $amount - $credit, 'currency' => $row->currency];
                }
            }

            return ['enabled' => true, 'events' => $events, 'unavailable' => array_keys($unavailable),
                'currency' => $currencies->count() === 1 ? $currencies->sole() : null];
        });
        $authority->principal($actor, $year);
        $gate->forget();

        return $gate->allows('fees') ? $snapshot : $off;
    }

    public function currency(array $snapshot, int $grade, string $start, string $end): ?string
    {
        $currencies = [];
        foreach ($snapshot['events'] as $event) {
            if ($event['grade_id'] === $grade && $event['date'] >= $start && $event['date'] <= $end) {
                $currencies[$event['currency']] = true;
            }
        }

        return count($currencies) === 1 ? array_key_first($currencies) : ($currencies === [] ? ($snapshot['currency'] ?? null) : null);
    }

    public function amount(array $snapshot, int $grade, string $start, string $end): ?int
    {
        if (! $snapshot['enabled'] || in_array($grade, $snapshot['unavailable'], true)) {
            return null;
        }
        $sum = 0;
        $currencies = [];
        foreach ($snapshot['events'] as $event) {
            if ($event['grade_id'] === $grade && $event['date'] >= $start && $event['date'] <= $end) {
                if ($event['cents'] > self::MAX_CENTS - $sum) {
                    return null;
                }
                $sum += $event['cents'];
                $currencies[$event['currency']] = true;
            }
        }

        return count($currencies) > 1 ? null : $sum;
    }

    private function cents(mixed $raw): ?int
    {
        if ((! is_int($raw) && ! is_string($raw)) || ! preg_match('/^(0|[1-9][0-9]*)$/D', (string) $raw)) {
            return null;
        }
        $value = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::MAX_CENTS]]);

        return $value === false ? null : $value;
    }
}
