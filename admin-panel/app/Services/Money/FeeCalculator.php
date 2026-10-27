<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\FeePlanPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Annual rate preview only. No guessed pro-rata, payer or financial writes. */
final class FeeCalculator
{
    public function preview(User $actor, int $yearId, string $date): array
    {
        app(FeePlanPolicy::class)->authorize($actor);
        Validator::make(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();

        return DB::transaction(function () use ($actor, $yearId, $date): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->first();
            app(FeePlanPolicy::class)->authorize($actor, locked: true);
            $year = SessionYear::findOrFail($yearId);
            app(FeePlans::class)->year($year);
            $start = substr((string) $year->start_date, 0, 10);
            $end = substr((string) $year->end_date, 0, 10);
            abort_unless($date >= $start && $date <= $end, 422, 'Choose a preview date inside the school year.');
            $plans = FeePlan::where('session_year_id', $yearId)->where('state', 'active')->orderBy('id')->get();
            $buckets = [];
            foreach ($plans as $plan) {
                $buckets[$plan->scope.':'.($plan->target_id ?? 0)][] = $plan;
            }
            $sections = ClassSection::get(['id', 'class_id'])->keyBy('id');
            $classes = ClassSchool::get(['id', 'grade_level_id'])->keyBy('id');
            $school = app(CurrentSchool::class)->id();
            $enrolments = Enrolment::where('session_year_id', $yearId)->onDate($date)->orderBy('learner_id')->get()->groupBy('learner_id');
            $people = DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')
                ->where('s.school_id', $school)->whereNull('s.deleted_at')->whereNull('u.deleted_at')
                ->whereIn('s.id', $enrolments->keys())->get(['s.id', 'u.first_name', 'u.last_name'])->keyBy('id');
            $rows = [];
            $totals = [];
            $exceptionCounts = [];
            foreach ($enrolments as $learnerId => $links) {
                $person = $people->get($learnerId);
                if ($person === null) {
                    continue;
                }
                $issues = [];
                $enrolment = $links->first();
                $section = $sections->get($enrolment->class_section_id);
                $class = $section === null ? null : $classes->get($section->class_id);
                if ($links->count() !== 1) {
                    $issues[] = 'Overlapping enrolments';
                }
                if ($links->contains(fn ($link) => $link->legacy_dates)) {
                    $issues[] = 'Confirm imported enrolment dates';
                }
                if ($class === null) {
                    $issues[] = 'Homeroom unavailable';
                }
                if ($enrolment->starts_on->toDateString() > $start || ($enrolment->ends_on !== null && $enrolment->ends_on->toDateString() < $end)) {
                    $issues[] = 'Pro-rata or leaver rule required';
                }
                $matches = [...($buckets['all:0'] ?? []), ...($buckets['class:'.($class->id ?? 0)] ?? []), ...($buckets['grade:'.($class->grade_level_id ?? 0)] ?? [])];
                if ($matches === []) {
                    $issues[] = 'No active fee plan';
                } elseif (count($matches) > 1) {
                    $issues[] = 'Multiple active fee plans';
                }
                $plan = count($matches) === 1 ? $matches[0] : null;
                if ($issues === [] && $plan !== null) {
                    $totals[$plan->currency] = ($totals[$plan->currency] ?? 0) + $plan->amount_cents;
                }
                foreach ($issues as $issue) {
                    $exceptionCounts[$issue] = ($exceptionCounts[$issue] ?? 0) + 1;
                }
                $rows[] = ['learner_id' => (int) $learnerId, 'name' => trim($person->first_name.' '.$person->last_name),
                    'plan_names' => array_map(fn ($match) => $match->name, $matches),
                    'amount_cents' => $plan?->amount_cents, 'currency' => $plan?->currency, 'issues' => $issues];
            }
            app(FeePlanPolicy::class)->authorize($actor, locked: true);
            ksort($totals);
            ksort($exceptionCounts);

            return ['year_id' => $yearId, 'date' => $date, 'rows' => $rows, 'totals' => $totals, 'exceptions' => $exceptionCounts,
                'learners' => count($rows), 'matched' => count(array_filter($rows, fn ($row) => $row['issues'] === []))];
        });
    }
}
