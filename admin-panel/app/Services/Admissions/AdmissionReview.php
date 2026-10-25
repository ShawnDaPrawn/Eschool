<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\IntakeGrade;
use App\Models\Admissions\Offer;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AdmissionReview
{
    public function decide(User $actor, int $id, array $input): Application
    {
        app(IntakeAuthority::class)->application($actor, $id, true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:waitlist,reject'], 'reason' => ['required', 'string', 'max:2000']])->validate();
        $d['application_id'] = $id;
        $result = app(AdmissionOperations::class)->once($actor, 'application.review', $d, function () use ($actor, $id, $d): array {
            $initial = Application::findOrFail($id);
            $grade = IntakeGrade::lockForUpdate()->findOrFail($initial->intake_grade_id);
            $row = Application::lockForUpdate()->findOrFail($id);
            app(IntakeAuthority::class)->application($actor, $id, true);
            abort_unless((int) $row->revision === (int) $d['revision'] && in_array($row->state, ['submitted', 'waitlisted', 'offered', 'accepted', 'offer_expired'], true), 409, 'Compare the current application before reviewing it.');
            Offer::where('application_id', $row->id)->whereIn('state', ['offered', 'accepted'])->update(['state' => 'withdrawn']);
            $waiting = $d['decision'] === 'waitlist';
            $row->update(['state' => $waiting ? 'waitlisted' : 'rejected', 'waitlisted_at' => $waiting ? now() : $row->waitlisted_at, 'terminal_at' => $waiting ? null : now(), 'revision' => $row->revision + 1]);
            app(AdmissionEvents::class)->record($row, $waiting ? 'waitlisted' : 'rejected', $actor, ['reason' => $d['reason']]);
            // Do not immediately re-offer a family deliberately returned to the
            // waiting list. Other released places are offered in FIFO order.
            if (! $waiting) {
                app(AdmissionOffers::class)->promote($grade, $actor);
            }
            app(ApplicationLinks::class)->issue($row, officeUpdate: true);

            return ['id' => (int) $row->id];
        });

        return app(IntakeAuthority::class)->application($actor, $result['id']);
    }

    public function officeFee(User $actor, int $id, array $input): Application
    {
        app(IntakeAuthority::class)->application($actor, $id, true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'amount_cents' => ['required', 'integer', 'min:0'], 'reference' => ['required', 'string', 'max:191']])->validate();
        $d['application_id'] = $id;
        $result = app(AdmissionOperations::class)->once($actor, 'office_fee.acknowledge', $d, function () use ($actor, $id, $d): array {
            $row = Application::lockForUpdate()->findOrFail($id);
            app(IntakeAuthority::class)->application($actor, $id, true);
            abort_unless((int) $row->revision === (int) $d['revision'] && (int) $row->office_fee_cents === (int) $d['amount_cents'] && ! $row->office_fee_paid
                && in_array($row->state, ['submitted', 'waitlisted', 'offered', 'accepted'], true), 409, 'Compare the application fee before recording an office acknowledgement.');
            $row->update(['office_fee_paid' => true, 'revision' => $row->revision + 1]);
            app(AdmissionEvents::class)->record($row, 'office_fee_acknowledged', $actor, ['amount_cents' => (int) $d['amount_cents'], 'reference' => $d['reference']]);

            return ['id' => (int) $row->id];
        });

        return app(IntakeAuthority::class)->application($actor, $result['id']);
    }
}
