<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\Admissions\Offer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdmissionOffers
{
    /** Accepted offers keep their place; expires_at is the acceptance deadline. */
    public function reserved(int $gradeId): int
    {
        return (int) IntakeGrade::findOrFail($gradeId)->occupied_places
            + Offer::where('intake_grade_id', $gradeId)->where(fn ($q) => $q->where('state', 'accepted')->orWhere(fn ($offered) => $offered->where('state', 'offered')->where('expires_at', '>', now()->utc())))->count();
    }

    private function create(Application $row, IntakeGrade $grade, ?User $actor): Offer
    {
        abort_if(app(IntakeRetention::class)->due($row), 404, 'This application is no longer available.');
        abort_unless(in_array($row->state, ['submitted', 'waitlisted'], true) && $row->email_verified_at !== null, 409, 'Only submitted or waiting applications can receive an offer.');
        abort_if($this->reserved((int) $grade->id) >= (int) $grade->places, 409, 'There is no unreserved place for this grade.');
        $offer = Offer::create(['application_id' => $row->id, 'intake_grade_id' => $grade->id, 'issued_by' => $actor?->id, 'state' => 'offered',
            'expires_at' => now()->addDays((int) config('admissions.offer_days')), 'revision' => 1]);
        $row->update(['state' => 'offered', 'terminal_at' => null, 'revision' => $row->revision + 1]);
        app(AdmissionEvents::class)->record($row, 'offer_issued', $actor, ['offer_id' => (int) $offer->id]);
        app(ApplicationLinks::class)->issue($row, officeUpdate: true);

        return $offer;
    }

    public function expireGrade(IntakeGrade $grade): void
    {
        foreach (Offer::where('intake_grade_id', $grade->id)->where('state', 'offered')->where('expires_at', '<=', now()->utc())->orderBy('id')->lockForUpdate()->get() as $offer) {
            $row = Application::select(['id', 'intake_id', 'intake_grade_id', 'state', 'email_verified_at', 'revision', 'waitlisted_at'])->lockForUpdate()->findOrFail($offer->application_id);
            $offer->update(['state' => 'expired', 'revision' => $offer->revision + 1]);
            if ($row->state === 'offered') {
                $row->update(['state' => 'offer_expired', 'terminal_at' => $offer->expires_at, 'revision' => $row->revision + 1]);
                app(AdmissionEvents::class)->record($row, 'offer_expired', details: ['offer_id' => (int) $offer->id]);
            }
        }
    }

    /** Caller holds the grade mutex; a place is reserved in this same transaction. */
    public function promote(IntakeGrade $grade, ?User $actor = null): array
    {
        app(IntakeAuthority::class)->enabled(true);
        $this->expireGrade($grade);
        $intake = Intake::findOrFail($grade->intake_id);
        $days = max(1, min((int) $intake->keep_for_days, (int) config('admissions.maximum_keep_days')));
        if ($intake->closes_at->addDays($days)->lessThanOrEqualTo(now())) {
            return []; // All waiting applications in this intake are retention-due.
        }
        $ids = [];
        while ($this->reserved((int) $grade->id) < (int) $grade->places) {
            $next = Application::select(['id', 'intake_id', 'intake_grade_id', 'state', 'email_verified_at', 'revision', 'waitlisted_at'])->where('intake_grade_id', $grade->id)->where('state', 'waitlisted')->whereNotNull('email_verified_at')->orderBy('waitlisted_at')->orderBy('submitted_at')->orderBy('id')->lockForUpdate()->first();
            if ($next === null) {
                break;
            }
            $ids[] = (int) $this->create($next, $grade, $actor)->id;
        }

        return $ids;
    }

    public function issue(User $actor, array $input): array
    {
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'application_ids' => ['required', 'array', 'min:1', 'max:100'], 'application_ids.*' => ['required', 'integer', 'distinct', 'min:1']])->validate();

        return app(AdmissionOperations::class)->once($actor, 'offers.issue', $d, function () use ($actor, $d): array {
            $results = [];
            foreach ($d['application_ids'] as $id) {
                $row = Application::select(['id', 'intake_id', 'intake_grade_id', 'state', 'email_verified_at', 'revision', 'waitlisted_at'])->findOrFail($id);
                $grade = IntakeGrade::lockForUpdate()->findOrFail($row->intake_grade_id);
                $this->expireGrade($grade);
                // Existing waiting families retain order before new submissions.
                $this->promote($grade, $actor);
                $row = Application::select(['id', 'intake_id', 'intake_grade_id', 'state', 'email_verified_at', 'revision', 'waitlisted_at'])->lockForUpdate()->findOrFail($id);
                if ($row->state === 'offered') {
                    $results[] = ['application_id' => (int) $row->id, 'state' => 'offered'];

                    continue;
                }
                abort_if(app(IntakeRetention::class)->due($row), 404, 'This application is no longer available.');
                abort_unless(in_array($row->state, ['submitted', 'waitlisted'], true), 409, 'Review the current application state before issuing offers.');
                if ($this->reserved((int) $grade->id) >= (int) $grade->places) {
                    $row->update(['state' => 'waitlisted', 'waitlisted_at' => $row->waitlisted_at ?? now(), 'revision' => $row->revision + 1]);
                    app(AdmissionEvents::class)->record($row, 'waitlisted', $actor);
                    $results[] = ['application_id' => (int) $row->id, 'state' => 'waitlisted'];
                } else {
                    $offer = $this->create($row, $grade, $actor);
                    $results[] = ['application_id' => (int) $row->id, 'state' => 'offered', 'offer_id' => (int) $offer->id];
                }
            }

            return ['applications' => $results];
        });
    }

    public function decide(int $id, string $key, int $revision, string $decision): Application
    {
        $initial = app(IntakeAuthority::class)->owner($id, $key, true);
        abort_unless(in_array($decision, ['accept', 'decline'], true), 422);

        return DB::transaction(function () use ($initial, $id, $key, $revision, $decision): Application {
            $grade = IntakeGrade::lockForUpdate()->findOrFail($initial->intake_grade_id);
            $this->expireGrade($grade);
            $row = Application::select(['id', 'intake_id', 'intake_grade_id', 'state', 'email_verified_at', 'revision', 'waitlisted_at'])->lockForUpdate()->findOrFail($id);
            app(IntakeAuthority::class)->owner($id, $key, true);
            if ($decision === 'decline' && $row->state === 'withdrawn' && (int) $row->revision === $revision + 1
                && Offer::where('application_id', $row->id)->where('state', 'declined')->exists()) {
                return $row;
            }
            $offer = Offer::where('application_id', $row->id)->whereIn('state', ['offered', 'accepted'])->orderByDesc('id')->lockForUpdate()->firstOrFail();
            if ($decision === 'accept' && $row->state === 'accepted' && (int) $row->revision === $revision + 1) {
                return $row;
            }
            abort_unless((int) $row->revision === $revision && $row->email_verified_at !== null, 409, 'Compare the current offer before responding.');
            if ($decision === 'accept') {
                abort_unless($row->state === 'offered' && $offer->state === 'offered' && $offer->expires_at->greaterThan(now()), 409, 'This offer is no longer available.');
                $offer->update(['state' => 'accepted', 'accepted_at' => now(), 'revision' => $offer->revision + 1]);
                $row->update(['state' => 'accepted', 'revision' => $row->revision + 1]);
                app(AdmissionEvents::class)->record($row, 'offer_accepted', details: ['offer_id' => (int) $offer->id]);
            } else {
                $offer->update(['state' => 'declined', 'revision' => $offer->revision + 1]);
                $row->update(['state' => 'withdrawn', 'terminal_at' => now(), 'revision' => $row->revision + 1]);
                app(AdmissionEvents::class)->record($row, 'offer_declined', details: ['offer_id' => (int) $offer->id]);
                $this->promote($grade);
            }
            app(IntakeAuthority::class)->owner($id, $key, true);

            return $row;
        }, 3);
    }

    public function recover(int $gradeId): array
    {
        app(IntakeAuthority::class)->enabled(true);

        return DB::transaction(function () use ($gradeId): array {
            $grade = IntakeGrade::lockForUpdate()->findOrFail($gradeId);

            return $this->promote($grade);
        }, 3);
    }
}
