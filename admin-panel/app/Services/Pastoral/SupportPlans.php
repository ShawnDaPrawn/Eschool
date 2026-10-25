<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\SupportAssignment;
use App\Models\Pastoral\SupportDisclosure;
use App\Models\Pastoral\SupportPlan;
use App\Models\Pastoral\SupportPlanVersion;
use App\Models\Pastoral\SupportReview;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SupportPlans
{
    public static function rules(string $action): array
    {
        $uuid = ['operation_id' => ['required', 'uuid']];
        $revision = ['revision' => ['required', 'integer', 'min:1']];
        $reason = ['reason' => ['required', 'string', 'min:3', 'max:2000']];

        return match ($action) {
            'assign' => $uuid + $reason + ['learner_id' => ['required', 'integer', 'min:1'], 'user_id' => ['required', 'integer', 'min:1'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on']],
            'save' => $uuid + ['learner_id' => ['required', 'integer', 'min:1'], 'revision' => ['sometimes', 'integer', 'min:1'], 'started_on' => ['required', 'date_format:Y-m-d'],
                'sias_stage' => ['required', 'string', 'max:100'], 'needs' => ['required', 'string', 'max:20000'], 'accommodations' => ['required', 'string', 'max:20000'], 'review_due_on' => ['nullable', 'date_format:Y-m-d']],
            'review' => $uuid + $revision + ['reviewed_on' => ['required', 'date_format:Y-m-d'], 'next_review_on' => ['nullable', 'date_format:Y-m-d'], 'outcome' => ['required', 'string', 'max:10000']],
            'disclose' => $uuid + $revision + $reason + ['user_id' => ['required', 'integer', 'min:1'], 'expires_on' => ['required', 'date_format:Y-m-d'], 'accommodations' => ['required', 'string', 'max:10000'], 'confirmation' => ['required', 'accepted']],
            'close','revoke-assignment','revoke-disclosure' => $uuid + $revision + $reason,
        };
    }

    private function data(string $action, array $input): array
    {
        return Validator::make($input, self::rules($action))->validate();
    }

    private function today(): string
    {
        return app(AcademicAuthority::class)->today();
    }

    private function bounded(string $date, int $days = 366): void
    {
        abort_unless($date >= $this->today() && CarbonImmutable::parse($this->today())->diffInDays(CarbonImmutable::parse($date)) <= $days, 422, 'Choose a date within the next year.');
    }

    private function snapshot(SupportPlan $plan, User $actor): void
    {
        SupportPlanVersion::create(['support_plan_id' => $plan->id, 'author_id' => $actor->id, 'revision' => $plan->revision,
            'snapshot' => ['started_on' => $plan->started_on->toDateString(), 'review_due_on' => $plan->review_due_on?->toDateString(),
                'state' => $plan->state, 'contents' => $plan->contents, 'accommodation_revision' => $plan->accommodation_revision]]);
    }

    public function assign(User $actor, array $input): SupportAssignment
    {
        $a = app(SupportAuthority::class);
        $a->principal($actor, true);
        $d = $this->data('assign', $input);
        $authorize = fn () => $a->principal($actor, true);

        /** @var SupportAssignment */
        return app(SupportOperation::class)->run($actor, $d['operation_id'], 'assign', $d, $authorize, function () use ($actor, $a, $d) {
            $a->learner((int) $d['learner_id'], true);
            $a->professional(User::findOrFail((int) $d['user_id']));
            $this->bounded($d['starts_on']);
            $this->bounded($d['ends_on']);
            abort_if(SupportAssignment::where('learner_id', $d['learner_id'])->where('user_id', $d['user_id'])->where('active', true)
                ->whereDate('starts_on', '<=', $d['ends_on'])->whereDate('ends_on', '>=', $d['starts_on'])->exists(), 409, 'This support appointment overlaps an existing appointment.');
            $row = SupportAssignment::create(['learner_id' => $d['learner_id'], 'user_id' => $d['user_id'], 'assigned_by' => $actor->id, 'starts_on' => $d['starts_on'], 'ends_on' => $d['ends_on'], 'reason' => $d['reason'], 'active' => true, 'revision' => 1]);
            Audit::record('support.assignment_added', $row, [], ['revision' => 1]);

            return $row;
        });
    }

    public function save(User $actor, array $input, ?int $id = null): SupportPlan
    {
        $a = app(SupportAuthority::class);
        $a->professional($actor, true);
        $d = $this->data('save', $input);
        $authorize = function () use ($actor, $a, $d, $id) {
            $a->caseload($actor, (int) $d['learner_id'], true);
            if ($id !== null) {
                $p = $a->plan($actor, $id, true);
                abort_unless((int) $p->learner_id === (int) $d['learner_id'], 404);
            }
        };

        /** @var SupportPlan */
        return app(SupportOperation::class)->run($actor, $d['operation_id'], 'save', [$id, $d], $authorize, function () use ($actor, $a, $d, $id) {
            $a->learner((int) $d['learner_id'], $id === null);
            abort_if($d['started_on'] > $this->today(), 422, 'A support plan cannot start in the future.');
            if (($d['review_due_on'] ?? null) !== null) {
                $this->bounded($d['review_due_on']);
            }
            $row = $id === null ? new SupportPlan : SupportPlan::lockForUpdate()->findOrFail($id);
            if ($row->exists) {
                abort_unless($row->state === 'active' && isset($d['revision']) && $row->revision === (int) $d['revision'], 409, 'This support plan changed. Compare its current revision.');
            } else {
                abort_if(SupportPlan::where('learner_id', $d['learner_id'])->where('state', 'active')->exists(), 409, 'This learner already has an active support plan.');
            }
            $changedAccommodations = ! $row->exists || ($row->contents['accommodations'] ?? null) !== $d['accommodations'];
            if ($row->exists && SupportReview::where('support_plan_id', $row->id)->exists()) {
                abort_unless($row->started_on->toDateString() === $d['started_on'], 409, 'Retain the start date of a reviewed support plan.');
            }
            $accommodationRevision = $row->exists ? $row->accommodation_revision + ($changedAccommodations ? 1 : 0) : 1;
            $revision = $row->exists ? $row->revision + 1 : 1;
            $row->fill(['learner_id' => $d['learner_id'], 'created_by' => $row->created_by ?? $actor->id, 'started_on' => $d['started_on'], 'review_due_on' => $d['review_due_on'] ?? null,
                'state' => 'active', 'revision' => $revision, 'accommodation_revision' => $accommodationRevision, 'contents' => ['sias_stage' => trim($d['sias_stage']), 'needs' => $d['needs'], 'accommodations' => $d['accommodations']]])->save();
            if ($changedAccommodations) {
                SupportDisclosure::where('support_plan_id', $row->id)->where('active', true)->update(['active' => false, 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
            }
            $this->snapshot($row, $actor);
            Audit::record('support.plan_saved', $row, [], ['revision' => $revision, 'state' => 'active']);

            return $row;
        });
    }

    public function review(User $actor, int $id, array $input): SupportReview
    {
        $a = app(SupportAuthority::class);
        $a->plan($actor, $id, true);
        $d = $this->data('review', $input);
        $authorize = fn () => $a->plan($actor, $id, true);

        /** @var SupportReview */
        return app(SupportOperation::class)->run($actor, $d['operation_id'], 'review', [$id, $d], $authorize, function () use ($actor, $d, $id) {
            $plan = SupportPlan::lockForUpdate()->findOrFail($id);
            abort_unless($plan->state === 'active' && $plan->revision === (int) $d['revision'], 409, 'This support plan changed. Compare its current revision.');
            abort_unless($d['reviewed_on'] >= $plan->started_on->toDateString() && $d['reviewed_on'] <= $this->today(), 422, 'Choose an actual review date within this plan.');
            if (($d['next_review_on'] ?? null) !== null) {
                $this->bounded($d['next_review_on']);
            }
            $review = SupportReview::create(['support_plan_id' => $id, 'author_id' => $actor->id, 'reviewed_on' => $d['reviewed_on'], 'next_review_on' => $d['next_review_on'] ?? null, 'kind' => 'review', 'outcome' => $d['outcome']]);
            $plan->fill(['revision' => $plan->revision + 1, 'review_due_on' => $d['next_review_on'] ?? null])->save();
            $this->snapshot($plan, $actor);
            Audit::record('support.review_added', $review, [], ['revision' => $plan->revision]);

            return $review;
        });
    }

    public function close(User $actor, int $id, array $input): SupportPlan
    {
        $a = app(SupportAuthority::class);
        $a->plan($actor, $id, true);
        $d = $this->data('close', $input);
        $authorize = fn () => $a->plan($actor, $id, true);

        /** @var SupportPlan */
        return app(SupportOperation::class)->run($actor, $d['operation_id'], 'close', [$id, $d], $authorize, function () use ($actor, $d, $id) {
            $plan = SupportPlan::lockForUpdate()->findOrFail($id);
            abort_unless($plan->state === 'active' && $plan->revision === (int) $d['revision'], 409, 'This support plan changed. Compare its current revision.');
            SupportReview::create(['support_plan_id' => $id, 'author_id' => $actor->id, 'reviewed_on' => $this->today(), 'kind' => 'closure', 'outcome' => $d['reason']]);
            SupportDisclosure::where('support_plan_id', $id)->where('active', true)->update(['active' => false, 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
            $plan->fill(['state' => 'closed', 'revision' => $plan->revision + 1])->save();
            $this->snapshot($plan, $actor);
            Audit::record('support.closed', $plan, [], ['revision' => $plan->revision, 'state' => 'closed']);

            return $plan;
        });
    }

    public function disclose(User $actor, int $id, array $input): SupportDisclosure
    {
        $a = app(SupportAuthority::class);
        $a->plan($actor, $id, true);
        $d = $this->data('disclose', $input);
        $authorize = fn () => $a->plan($actor, $id, true);

        /** @var SupportDisclosure */
        return app(SupportOperation::class)->run($actor, $d['operation_id'], 'disclose', [$id, $d], $authorize, function () use ($actor, $a, $d, $id) {
            $plan = SupportPlan::lockForUpdate()->findOrFail($id);
            abort_unless($plan->state === 'active' && $plan->revision === (int) $d['revision'], 409, 'This support plan changed. Compare its current revision.');
            $a->teacher(User::findOrFail((int) $d['user_id']));
            $this->bounded($d['expires_on']);
            abort_if(SupportDisclosure::where('support_plan_id', $id)->where('user_id', $d['user_id'])->where('active', true)->whereDate('expires_on', '>=', $this->today())->exists(), 409, 'Revoke the existing accommodation disclosure before replacing it.');
            $row = SupportDisclosure::create(['support_plan_id' => $id, 'learner_id' => $plan->learner_id, 'user_id' => $d['user_id'], 'approved_by' => $actor->id, 'expires_on' => $d['expires_on'],
                'active' => true, 'revision' => 1, 'source_accommodation_revision' => $plan->accommodation_revision, 'accommodations' => $d['accommodations'], 'reason' => $d['reason']]);
            Audit::record('support.disclosed', $row, [], ['revision' => 1]);

            return $row;
        });
    }

    public function revoke(User $actor, string $kind, int $id, array $input): SupportAssignment|SupportDisclosure
    {
        abort_unless(in_array($kind, ['assignment', 'disclosure'], true), 422);
        $a = app(SupportAuthority::class);
        if ($kind === 'assignment') {
            $a->principal($actor, true);
            $row = SupportAssignment::findOrFail($id);
        } else {
            $a->professional($actor, true);
            $row = SupportDisclosure::findOrFail($id);
            $a->plan($actor, (int) $row->support_plan_id, true);
        }
        $d = $this->data('revoke-'.$kind, $input);
        $authorize = function () use ($actor, $a, $kind, $row) {
            if ($kind === 'assignment') {
                $a->principal($actor, true);
            } else {
                $a->plan($actor, (int) $row->support_plan_id, true);
            }
        };

        /** @var SupportAssignment|SupportDisclosure */
        return app(SupportOperation::class)->run($actor, $d['operation_id'], 'revoke-'.$kind, [$id, $d], $authorize, function () use ($kind, $id, $d) {
            $class = $kind === 'assignment' ? SupportAssignment::class : SupportDisclosure::class;
            $row = $class::lockForUpdate()->findOrFail($id);
            abort_unless($row->revision === (int) $d['revision'] && $row->active, 409, 'This support appointment or disclosure changed.');
            $row->fill(['active' => false, 'revision' => $row->revision + 1, 'revoke_reason' => $d['reason']])->save();
            Audit::record('support.'.$kind.'_revoked', $row, [], ['revision' => $row->revision, 'active' => false]);

            return $row;
        });
    }
}
