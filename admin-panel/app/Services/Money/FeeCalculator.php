<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeDiscount;
use App\Models\Money\FeeLifecyclePolicy;
use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Canonical annual rates and reviewed original-charge reductions. */
final class FeeCalculator
{
    public function reduction(User $actor, FeeDiscount $rule, FeeAccount $account, int $learner, string $operation): array
    {
        return app(FeeDiscountCalculator::class)->snapshot($actor, $rule, $account, $learner, $operation);
    }

    public function preview(User $actor, int $yearId, string $date): array
    {
        return $this->calculate($actor, $yearId, $date, 'fee-plans-manage', false);
    }

    public function forBilling(User $actor, int $yearId, string $date): array
    {
        return $this->calculate($actor, $yearId, $date, 'fee-billing-manage', true);
    }

    private function calculate(User $actor, int $yearId, string $date, string $permission, bool $locked): array
    {
        app(MoneyPolicy::class)->authorize($actor, $permission);
        Validator::make(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();

        return DB::transaction(function () use ($actor, $yearId, $date, $permission, $locked): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->first();
            app(MoneyPolicy::class)->authorize($actor, $permission, locked: true);
            $year = SessionYear::when($locked, fn ($q) => $q->lockForUpdate())->findOrFail($yearId);
            app(FeePlans::class)->year($year);
            $start = substr((string) $year->start_date, 0, 10);
            $end = substr((string) $year->end_date, 0, 10);
            abort_unless($date >= $start && $date <= $end, 422, 'Choose a preview date inside the school year.');
            $lifecyclePolicy = FeeLifecyclePolicy::where('session_year_id', $yearId)->lockForUpdate()->first();
            $plans = FeePlan::where('session_year_id', $yearId)->where('state', 'active')->orderBy('id')->when($locked, fn ($q) => $q->lockForUpdate())->get();
            $buckets = [];
            foreach ($plans as $plan) {
                $buckets[$plan->scope.':'.($plan->target_id ?? 0)][] = $plan;
            }
            $sections = ClassSection::when($locked, fn ($q) => $q->lockForUpdate())->get(['id', 'class_id'])->keyBy('id');
            $classes = ClassSchool::when($locked, fn ($q) => $q->lockForUpdate())->get(['id', 'grade_level_id'])->keyBy('id');
            $school = app(CurrentSchool::class)->id();
            $enrolments = Enrolment::where('session_year_id', $yearId)->onDate($date)->orderBy('learner_id')->when($locked, fn ($q) => $q->lockForUpdate())->get()->groupBy('learner_id');
            $enrolmentHistory = Enrolment::where('session_year_id', $yearId)->whereIn('learner_id', $enrolments->keys())->orderBy('learner_id')->orderBy('starts_on')->when($locked, fn ($q) => $q->lockForUpdate())->get()->groupBy('learner_id');
            $people = DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')
                ->where('s.school_id', $school)->whereNull('s.deleted_at')->whereNull('u.deleted_at')
                ->whereIn('s.id', $enrolments->keys())->when($locked, fn ($q) => $q->lockForUpdate())->get(['s.id', 's.user_id', 'u.first_name', 'u.last_name'])->keyBy('id');
            $phases = GradeLevel::pluck('phase', 'id');
            $ruleContext = app(FeePlanRules::class)->context($plans->flatMap(fn ($p) => $p->applies_to ?? [])->all(), $people->pluck('user_id')->all(), $locked);
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
                $attendance = app(FeeEnrolmentPeriods::class)->continuous($enrolmentHistory->get($learnerId));
                $issues = [...$issues, ...$attendance['issues']];
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

                $matches = [...($buckets['all:0'] ?? []), ...($buckets['class:'.($class->id ?? 0)] ?? []), ...($buckets['grade:'.($class->grade_level_id ?? 0)] ?? [])];
                $eligible = [];
                foreach ($matches as $candidate) {
                    try {
                        if (app(FeePlanRules::class)->matches($candidate->applies_to ?? [], $phases->get($class?->grade_level_id), (int) $person->user_id, $ruleContext)) {
                            $eligible[] = $candidate;
                        }
                    } catch (ValidationException) {
                        $issues[] = 'Fee plan rule inputs need review';
                    }
                }
                $matches = $eligible;
                if ($matches === []) {
                    $issues[] = 'No active fee plan';
                } elseif (count($matches) > 1) {
                    $issues[] = 'Multiple active fee plans';
                }
                $plan = count($matches) === 1 ? $matches[0] : null;
                $price = null;
                if ($plan !== null) {
                    try {
                        $price = app(FeeProration::class)->charge($plan->amount_cents, $start, $end, $attendance['starts_on'], $attendance['ends_on'], $lifecyclePolicy);
                    } catch (HttpExceptionInterface $error) {
                        if ($error->getStatusCode() !== 422) {
                            throw $error;
                        }
                        $issues[] = 'Pro-rata or leaver rule required';
                    }
                }
                if ($issues === [] && $plan !== null && $price !== null) {
                    $totals[$plan->currency] = ($totals[$plan->currency] ?? 0) + $price['amount_cents'];
                }
                $issues = array_values(array_unique($issues));
                foreach ($issues as $issue) {
                    $exceptionCounts[$issue] = ($exceptionCounts[$issue] ?? 0) + 1;
                }
                $rows[] = ['enrolment_id' => (int) $enrolment->id, 'enrolment_start' => $attendance['starts_on'], 'enrolment_end' => $attendance['ends_on'], 'enrolment_segments' => $attendance['segments'], 'learner_id' => (int) $learnerId, 'name' => trim($person->first_name.' '.$person->last_name),
                    'plan_names' => array_map(fn ($match) => $match->name, $matches),
                    'plan_id' => $plan?->id, 'plan_revision' => $plan?->revision,
                    'amount_cents' => $price['amount_cents'] ?? null, 'annual_cents' => $plan?->amount_cents, 'period_start' => $price['period_start'] ?? $start, 'period_end' => $price['period_end'] ?? $end, 'proration' => $price['proration'] ?? null, 'currency' => $plan?->currency, 'issues' => $issues];
            }
            app(MoneyPolicy::class)->authorize($actor, $permission, locked: true);
            ksort($totals);
            ksort($exceptionCounts);

            return ['year_id' => $yearId, 'year_start' => $start, 'year_end' => $end, 'date' => $date, 'rows' => $rows, 'totals' => $totals, 'exceptions' => $exceptionCounts,
                'learners' => count($rows), 'matched' => count(array_filter($rows, fn ($row) => $row['issues'] === []))];
        });
    }
}
